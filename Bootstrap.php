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

use \Shopware_Plugins_Frontend_LfndsDonation_Migration_MigrationManager as MigrationManager;
use \Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager as LocaleManager;
use \Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager as ConfigurationManager;
use \Shopware_Plugins_Frontend_LfndsDonation_Events_PreDispatchCheckoutEvent as PreDispatchCheckoutEvent;
use \Shopware_Plugins_Frontend_LfndsDonation_Events_PostDispatchCheckoutEvent as PostDispatchCheckoutEvent;
use \Shopware_Plugins_Frontend_LfndsDonation_Events_PositionRemovedFromOrderEvent as PositionRemovedFromOrderEvent;
use \Shopware_Plugins_Frontend_LfndsDonation_Events_BeforeAssignValuesToDocumentEvent as BeforeAssignValuesToDocumentEvent;

require_once __DIR__ . '/SDK/Lfnds/Facade.php';

/**
 * Bootstrap for the elefunds module.
 *
 * @package    elefunds Shopware Module
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 1.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

    const VERSION = '2.0.0';

    /**
     * Initializes the configuration form,  creates the database schema and subscribes
     * to checkout and document creation events.
     *
     * @return bool
     */
    public function install()
    {

        $this->subscribeEvent(
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout',
            'onPreDispatchCheckout'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout',
            'onPostDispatchCheckout',
            0
        );

        $this->subscribeEvent(
            'Shopware_Components_Document::assignValues::after',
            'onBeforeAssignValuesToDocument'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PreDispatch_Backend_Order',
            'onPositionRemovedFromOrder'
        );

        $migration = new MigrationManager(self::VERSION);
        return $migration->setUp(array(
                'pluginId' => $this->getId(),
                'form'  =>  $this->Form()
            )
        );
    }

    /**
     * Invokes the migration manager.
     *
     * @param string $version
     * @return bool
     */
    public function update($version) {
        $migration = new MigrationManager(self::VERSION);
        $migration->setUp(
            array(
            'pluginId' => $this->getId(),
            'form'  =>  $this->Form()
            )
        );
        return $migration->migrateFrom($version);
    }

    /**
     * Removes the elefunds footprint from the the system.
     *
     * @return bool
     */
    public function uninstall() {
        $migration = new MigrationManager(self::VERSION);
        return $migration->remove();
    }

    /**
     * Registers the custom models used in this plugin.
     *
     * @return void
     */
    public function afterInit() {
        $this->registerCustomModels();
    }

    /**
     * Prepares the donation if finish action is about to be
     * executed.
     *
     * @param Enlight_Controller_EventArgs $args
     * @return void
     */
    public function onPreDispatchCheckout(Enlight_Controller_EventArgs $args) {
        $event = new PreDispatchCheckoutEvent();
        $event->execute($args);
    }

    /**
     * Injects the donation module if action is confirm and processes the donation
     * if everything is finished.
     *
     * @param Enlight_Controller_EventArgs $args
     * @return void
     */
    public function onPostDispatchCheckout(Enlight_Controller_EventArgs $args) {
        $event = new PostDispatchCheckoutEvent();
        $event->execute($args, array('path' => $this->Path()));
    }

    /**
     * Cancels the donation when it's removed from an order in the backend.
     *
     * @param Enlight_Controller_EventArgs $args
     * @return void
     */
    public function onPositionRemovedFromOrder(Enlight_Controller_EventArgs $args) {
        $event = new PositionRemovedFromOrderEvent();
        $event->execute($args);
    }

    /**
     * Adds the obligatory additional invoice text to a receipt with a donation.
     *
     * We do remove the donation from shipping notes.
     *
     * @param Enlight_Hook_HookArgs $args
     * @return void
     */
    public function onBeforeAssignValuesToDocument(Enlight_Hook_HookArgs $args) {
        $event = new BeforeAssignValuesToDocumentEvent();
        $event->execute($args);
    }

    /**
     * Summarizes all information about the elefunds module.
     *
     * @return array
     */
    public function getInfo() {
        return array(
            'version'       => $this->getVersion(),
            'label'         => 'elefunds Donation Module',
            'description'   => LocaleManager::loadFromFile('description.html'),
            'author'        => 'elefunds GmbH',
            'copyright'     => 'Copyright © 2013, elefunds GmbH',
            'support'       => 'https://elefunds.de',
            'link'          => 'https://elefunds.de'
        );
    }

    /**
     * Wrapper for the version string.
     *
     * We can not return self::VERSION here - as the plugin uploader from shopware is stupid as hell
     * and does not accept that.
     *
     * @return string
     */
    public function getVersion() {
        return '2.0.0';
    }

}