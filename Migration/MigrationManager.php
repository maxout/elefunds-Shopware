<?php

/**
 * elefunds Shopware Module
 *
 * Copyright (c) 2012-2013, elefunds GmbH <hello@elefunds.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of the elefunds GmbH nor the names of its
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

use Shopware_Plugins_Frontend_LfndsDonation_Migration_Branches_BranchInterface as BranchInterface;
use Shopware_Plugins_Frontend_LfndsDonation_Manager_SyncManager as SyncManager;
use Shopware_Plugins_Frontend_LfndsDonation_Migration_SchemaManager as SchemaManager;
use Shopware_Plugins_Frontend_LfndsDonation_Migration_FormManager as FormManager;

/**
 * Takes care of the migration process between two modules.
 *
 * @package    elefunds Shopware Module
 * @subpackage Migration
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 2.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Migration_MigrationManager {

    /**
     * @var int
     */
    protected $currentMajor;

    /**
     * @var int
     */
    protected $currentMinor;

    /**
     * @var int
     */
    protected $currentBugfix;

    /**
     * @var SchemaManager
     */
    protected $schema;

    /**
     * @param string $currentVersion
     */
    public function __construct($currentVersion) {

        $version = explode('.', $currentVersion);
        $this->currentMajor = (int)$version[0];
        $this->currentMinor = (int)$version[1];
        $this->currentBugfix = (int)$version[2];

        $this->schema = new SchemaManager();
    }

    /**
     * Receives an Array with some environment variables for processing, as:
     *
     * - a 'form' instance of the plugin
     * - the 'pluginId'
     *
     * ... and sets up migration and schema management.
     *
     * @param array $env
     * @return bool
     */
    public function setUp($env) {
        $this->schema->create();

        $form = new FormManager($env['pluginId'], $env['form']);
        $form->create();

        return TRUE;
    }

    /**
     * Removes elefunds from the system.
     *
     * @return bool
     */
    public function remove() {
        $this->invokeApiSync();
        $this->schema->drop();

        return TRUE;
    }

    /**
     * Iterates through all necessary and available branch files and performs migration.
     *
     * @param string $oldVersion
     * @return bool
     */
    public function migrateFrom($oldVersion) {

        list($majorRelease, $minorRelease, $bugfixRelease) = array_map(function($version) {
                                                                return (int)$version;
                                                             }, explode('.', $oldVersion));

        $successfulMigrations = array();
        while ($majorRelease < $this->currentMajor) {
            $branchFile = __DIR__ . '/Branches/Branch' . $majorRelease . '.php';
            if (is_readable($branchFile)) {
                $branchClass = 'Shopware_Plugins_Frontend_LfndsDonation_Migration_Branches_Branch' . $majorRelease;

                /** @var BranchInterface $branch */
                $branch = new $branchClass();
                $branch->setVersion($majorRelease, $minorRelease, $bugfixRelease);
                $successfulMigrations[] = $branch->migrate();
            }
            ++$majorRelease;
        }

        $this->invokeApiSync();
        return !in_array(FALSE, $successfulMigrations);
    }

    /**
     * Syncs all donations with the API
     *
     * @return void
     */
    protected function invokeApiSync() {
        $syncManager = new SyncManager();
        $syncManager->syncDonations();
    }

}