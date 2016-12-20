<?php
/*
This file is part of SeAT

Copyright (C) 2015, 2016  Leon Jacobs

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

namespace Seat\Eveapi\Api\Corporation;

use Seat\Eveapi\Api\Base;
use Seat\Eveapi\Models\Corporation\IndustryJob;

/**
 * Class IndustryJobs
 * @package Seat\Eveapi\Api\Corporation
 */
class IndustryJobs extends Base
{

    /**
     * Run the Update
     *
     * @return mixed|void
     */
    public function call()
    {

        $pheal = $this->setScope('corp')
            ->setCorporationID()->getPheal();

        $result = $pheal->IndustryJobs();

        $this->writeJobLog('industryjobs',
            'API responsed with ' . count($result->jobs) . ' jobs');

        foreach ($result->jobs as $industry_job) {

            $new_industry_job = IndustryJob::firstOrNew([
                'corporationID' => $this->corporationID,
                'jobID'         => $industry_job->jobID,
            ]);

            $new_industry_job->fill([
                'installerID'          => $industry_job->installerID,
                'installerName'        => $industry_job->installerName,
                'facilityID'           => $industry_job->facilityID,
                'solarSystemID'        => $industry_job->solarSystemID,
                'solarSystemName'      => $industry_job->solarSystemName,
                'stationID'            => $industry_job->stationID,
                'activityID'           => $industry_job->activityID,
                'blueprintID'          => $industry_job->blueprintID,
                'blueprintTypeID'      => $industry_job->blueprintTypeID,
                'blueprintTypeName'    => $industry_job->blueprintTypeName,
                'blueprintLocationID'  => $industry_job->blueprintLocationID,
                'outputLocationID'     => $industry_job->outputLocationID,
                'runs'                 => $industry_job->runs,
                'cost'                 => $industry_job->cost,
                'teamID'               => $industry_job->teamID,
                'licensedRuns'         => $industry_job->licensedRuns,
                'probability'          => $industry_job->probability,
                'productTypeID'        => $industry_job->productTypeID,
                'productTypeName'      => $industry_job->productTypeName,
                'status'               => $industry_job->status,
                'timeInSeconds'        => $industry_job->timeInSeconds,
                'startDate'            => $industry_job->startDate,
                'endDate'              => $industry_job->endDate,
                'pauseDate'            => $industry_job->pauseDate,
                'completedDate'        => $industry_job->completedDate,
                'completedCharacterID' => $industry_job->completedCharacterID,
                'successfulRuns'       => $industry_job->successfulRuns
            ]);

            $new_industry_job->save();

        } // Foreach Industry Job

        return;
    }
}
