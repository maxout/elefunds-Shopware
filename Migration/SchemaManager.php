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

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Shopware\Components\Model\ModelManager;

/**
 * Takes care of the schema processing.
 *
 * @package    elefunds Shopware Module
 * @subpackage Migration
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 2.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Migration_SchemaManager {

    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var SchemaTool
     */
    protected $schemaTool;

    public function __construct() {
        $this->modelManager = Shopware()->Models();
        $this->modelManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $this->schemaTool = new SchemaTool($this->modelManager);
    }

    /**
     * Creates the needed table:
     *
     *      * Donation to save made donation to be pushed to the API upon next sync
     *
     * @return void
     */
    public function create() {

       try {
           $this->schemaTool->createSchema(
                array(
                    $this->modelManager->getClassMetadata('Shopware\CustomModels\Elefunds\Donation\Donation'),
                )
            );
       } catch (ToolsException $exception) {
           // Class already exists
       }
    }

    /**
     * Removes the entire elefunds footprint from the database.
     *
     * @return void
     */
    public function drop() {
        $this->schemaTool->dropSchema(
            array(
                $this->modelManager->getClassMetadata('Shopware\CustomModels\Elefunds\Donation\Donation')
            )
        );
    }
}