<?php

/**
 * elefunds Shopware Module
 *
 * Copyright (c) 2012-2013, elefunds GmbH <contact@elefunds>.
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

/**
 * Takes care of the migration process for the 1.x.x Branch
 *
 * @package    elefunds Shopware Module
 * @subpackage Migration
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <contact@elefunds>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 2.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Migration_Branches_Branch1 implements BranchInterface {

    /**
     * @var int
     */
    protected $major;

    /**
     * @var int
     */
    protected $minor;

    /**
     * @var int
     */
    protected $bugfix;

    /**
     * Migrates the given version to current.
     *
     * @return bool
     */
    public function migrate()
    {
        if ($this->minor <= 2) {
            try {
                Shopware()->Db()->query("DELETE FROM s_plugin_elefunds_donation WHERE ISNULL(receiver_ids) OR receiver_ids = ''");
            } catch (Zend_Db_Statement_Exception $exeption) {
                // Table does not exist, it's bad. This should only fail in totally f****d up extensions.
                // We silently pass, to give them a chance to uninstall the plugin.
            }
        }

        Shopware()->DB()->query("DROP TABLE IF EXISTS s_plugin_elefunds_receiver");
        Shopware()->DB()->query("ALTER TABLE s_plugin_elefunds_donation MODIFY COLUMN donator_zip VARCHAR(12)");
        return TRUE;
    }

    /**
     * Sets the versions of the version to migrate FROM.
     *
     * @param int $majorRelease
     * @param int $minorRelease
     * @param int $bugfixRelease
     * @return BranchInterface
     */
    public function setVersion($majorRelease, $minorRelease, $bugfixRelease)
    {
        $this->major = $majorRelease;
        $this->minor = $minorRelease;
        $this->bugfix = $bugfixRelease;
    }

}