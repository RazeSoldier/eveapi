<?php
/*
This file is part of SeAT

Copyright (C) 2015  Leon Jacobs

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

namespace Seat\Eveapi\Api;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Pheal\Access\StaticCheck;
use Pheal\Cache\HashedNameFileStorage;
use Pheal\Core\Config;
use Pheal\Log\PsrLogger;
use Pheal\Pheal;
use Seat\Eveapi\Models\EveApiKey;
use Seat\Eveapi\Traits\Validation;

/**
 * This abstract basically just containers the
 * few Traits that are used in all API update
 * methods
 *
 * Class Base
 * @package Seat\Eveapi\Api
 */
abstract class Base
{

    use Validation;

    /**
     * @var null
     */
    protected $pheal = null;

    /**
     * @var null
     */
    protected $api_info = null;

    /**
     * @var null
     */
    protected $key_id = null;

    /**
     * @var null
     */
    protected $v_code = null;

    /**
     * @var null
     */
    protected $scope = null;

    /**
     * @var null
     */
    protected $logger = null;

    /**
     * The contract for the update call. All
     * update should at least have this function
     *
     * @return mixed
     */
    abstract protected function call();

    /**
     * Setup the updater instance
     */
    public function __construct()
    {

        $this->setup();

    }

    /**
     * Configure a Psr-Style logger to be given
     * to PhealNG for logging requests. This logger
     * will rotate logs within a timespan of 30 days.
     *
     * @return \Monolog\Logger|null
     */
    private function getLogger()
    {

        // If its already setup, just return it.
        if (!is_null($this->logger))
            return $this->logger;

        // Configure the logger by setting the logfile
        // path and the format logs should be.
        $log_file = storage_path('logs/pheal.log');
        $format = new LineFormatter(null, null, false, true);

        $stream = new RotatingFileHandler($log_file, 30, Logger::INFO);
        $stream->setFormatter($format);

        $this->logger = new Logger('pheal');
        $this->logger->pushHandler($stream);

        return $this->logger;

    }

    /**
     * Configure PhealNG for use.
     *
     * @return $this
     */
    public function setup()
    {

        $config = Config::getInstance();

        // Configure Pheal
        $config->cache = new HashedNameFileStorage(storage_path() . '/app/pheal/');
        $config->access = new StaticCheck();
        $config->log = new PsrLogger($this->getLogger());
        $config->api_customkeys = true;
        $config->http_method = 'curl';
        $config->http_timeout = 60;

        // TODO: Setup the identifying User-Agent
        $config->http_user_agent = 'Testing SeAT 1.0 (harro foxfour!)';

        return $this;
    }

    /**
     * Sets the API credentials to use with API requests.
     *
     * @param \Seat\Eveapi\Models\EveApiKey $api_info
     *
     * @return $this
     * @throws \Seat\Eveapi\Exception\InvalidKeyPairException
     * @throws \Seat\Eveapi\Exception\MissingKeyPairException
     *
     */
    public function setApi(EveApiKey $api_info)
    {

        $this->validateKeyPair(
            $api_info->key_id,
            $api_info->v_code
        );

        // Set the key_id & v_code properties
        $this->key_id = $api_info->key_id;
        $this->v_code = $api_info->v_code;

        // Set the EveApiKey Object
        $this->api_info = $api_info;

        return $this;
    }

    /**
     * Configure the scope for which API calls will
     * be made
     *
     * @param $scope
     *
     * @return $this
     */
    public function setScope($scope)
    {

        $this->validateScope($scope);
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get a PhealNG instance. This method will prepare
     * the authentication details based on the properties
     * and return a ready to use Object
     *
     * @return null|\Pheal\Pheal
     */
    public function getPheal()
    {

        // Setup the Pheal instance with the key
        $this->pheal = new Pheal(
            $this->key_id,
            $this->v_code
        );

        // Check if a scope was set.
        if (!is_null($this->scope))
            $this->pheal->scope = $this->scope;

        return $this->pheal;

    }

    /**
     * Cleanup actions.
     */
    public function __destruct()
    {

        $this->pheal = null;
        $this->api_info = null;
        $this->scope = null;
        $this->logger = null;

        return;
    }

}