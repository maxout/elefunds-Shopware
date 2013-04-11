<?php

/**
 * elefunds Shopware Module
 *
 * Copyright (c) 2012, elefunds GmbH <hello@elefunds.de>.
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

require_once dirname(__FILE__) . '/../SDK/Template/Shop/CheckoutSuccessConfiguration.php';

/**
 * Configuration Manager for the Shopware module.
 *
 * @package    Configuration
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 1.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager {

    /**
     * Internal configuration contains data that can be manipulated by the user.
     *
     * @var array
     */
    protected static $internalConfiguration;

    /**
     * The default configuration that may be overridden by the internalConfig.php.
     *
     * @var array
     */
    protected static $defaultConfiguration;

    /**
     * Initializes the values for configuration.
     *
     * This file includes technical data that may not be changed.
     *
     * However, if shops have adjusted their software, it may be necessary to transparently modify the behaviour
     * of this plugin. If you do so, add a file named internalConfig.php in the configuration folder and adjust it
     * to your needs. It should just return an array that may override the fields of the default configuration.
     *
     * @return void
     */
    protected static function initInternalConfiguration() {

        $internalConfigurationFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'internalConfig.php';

        if (file_exists($internalConfigurationFile) && is_readable($internalConfigurationFile)) {
            self::$internalConfiguration = include($internalConfigurationFile);
        } else {
            self::$internalConfiguration = array();
        }

        self::$defaultConfiguration = array(
            'documents/shippingTemplate'       =>  'index_ls.tpl',
            'states/completed'                 =>  array(2),
            'states/cancelled'                 =>  array(4),
            'donations/daysToLookForPending'   =>  45,
            'module/widthInPixel/Top'          =>  996,
            'module/widthInPixel/Bottom'       =>  976,
            'share/includeSocialMediaShare'    =>  TRUE
        );
    }

    /**
     * Retrieves configuration for the plugin's setting page.
     *
     * @param string $name
     * @return mixed
     */
    public static function get($name) {
        return Shopware()->Plugins()->Frontend()->LfndsDonation()->Config()->get($name);
    }

    /**
     * Retrieves configuration from the internal settings.
     *
     * @param string $name
     * @return mixed
     */
    public static function getInternal($name) {
        if (self::$defaultConfiguration === NULL) {
            self::initInternalConfiguration();
        }

        if (isset(self::$internalConfiguration[$name])) {
            return self::$internalConfiguration[$name];
        } else {
            return(self::$defaultConfiguration[$name]);
        }
    }


    /**
     * Checks whether a given payment provider (by id) is among the selected
     * payment providers in our module configuration.
     *
     * @param $paymentProviderId
     * @return boolean
     */
    public static function isAcceptedPaymentProvider($paymentProviderId) {
        return Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager::get('elefundsPaymentProvider' . $paymentProviderId);
    }

}
