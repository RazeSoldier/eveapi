<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017, 2018  Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Seat\Eveapi\Jobs\Wallet\Corporation;

use Seat\Eveapi\Jobs\EsiBase;
use Seat\Eveapi\Models\Corporation\CorporationDivision;
use Seat\Eveapi\Models\Wallet\CorporationWalletJournal;

/**
 * Class Journals.
 * @package Seat\Eveapi\Jobs\Wallet\Corporation
 */
class Journals extends EsiBase
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/corporations/{corporation_id}/wallets/{division}/journal/';

    /**
     * @var string
     */
    protected $version = 'v3';

    /**
     * @var string
     */
    protected $scope = 'esi-wallet.read_corporation_wallets.v1';

    /**
     * @var array
     */
    protected $roles = ['Accountant', 'Junior_Accountant'];

    /**
     * @var array
     */
    protected $tags = ['corporation', 'wallet', 'journals'];

    /**
     * A counter used to walk the journal backwards.
     *
     * @var int
     */
    protected $from_id = PHP_INT_MAX;

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle()
    {

        if (! $this->authenticated()) return;

        CorporationDivision::where('corporation_id', $this->getCorporationId())->get()
            ->each(function ($division) {

                // Perform a journal walk backwards to get all of the
                // entries as far back as possible. When the response from
                // ESI is empty, we can assume we have everything.
                while (true) {

                    $this->query_string = ['from_id' => $this->from_id];

                    $journal = $this->retrieve([
                        'corporation_id' => $this->getCorporationId(),
                        'division'       => $division->division,
                    ]);

                    if ($journal->isCachedLoad()) return;

                    // If we have no more entries, break the loop.
                    if (collect($journal)->count() === 0)
                        break;

                    collect($journal)->each(function ($entry) use ($division) {

                        $journal_entry = CorporationWalletJournal::firstOrNew([
                            'corporation_id' => $this->getCorporationId(),
                            'division'       => $division->division,
                            'id'             => $entry->id,
                        ]);

                        // If this journal entry has already been recorded,
                        // move on to the next.
                        if ($journal_entry->exists)
                            return;

                        $journal_entry->fill([
                            'corporation_id'    => $this->getCorporationId(),
                            'division'          => $division->division,
                            'id'                => $entry->id,
                            'date'              => carbon($entry->date),
                            'ref_type'          => $entry->ref_type,
                            'first_party_id'    => $entry->first_party_id ?? null,
                            'second_party_id'   => $entry->second_party_id ?? null,
                            'amount'            => $entry->amount ?? null,
                            'balance'           => $entry->balance ?? null,
                            'reason'            => $entry->reason ?? null,
                            'tax_receiver_id'   => $entry->tax_receiver_id ?? null,
                            'tax'               => $entry->tax ?? null,
                            // introduced in v4
                            'description'       => $entry->description,
                            'context_id'        => $entry->context_id ?? null,
                            'context_type_id'   => $entry->context_type_id ?? null,
                        ])->save();

                    });

                    // Update the from_id to be the new lowest ref_id we
                    // know of. The next call will use this.
                    $this->from_id = collect($journal)->min('id') - 1;
                }

                // Reset the from_id for the next wallet division
                $this->from_id = PHP_INT_MAX;
            });
    }
}