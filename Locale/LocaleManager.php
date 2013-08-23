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

/**
 * Locals for the elefunds module.
 *
 * @package    elefunds Shopware Module
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 1.0.0
 */

class Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager
{
    /**
     * @var array
     */
    protected static $locale;

    /**
     * @var string
     */
    protected static $language;

    /**
     * Returns the detected Shopware Locale.
     *
     * @return string
     */
    public static function getLanguage() {
        if (self::$language === NULL) {
            self::$language = Shopware()->Locale()->getLanguage();
        }
        return self::$language;
    }

    /**
     * Initializes the values for localisation.
     *
     * @return void
     */
    protected static function init() {
        self::$locale = array(
            'de'    =>  array(
                'additionalInvoiceText'     => 'Die Spende wird vereinnahmt für die elefunds Stiftung gUG und zu 100% an die ausgewählten Organisationen weitergeleitet. Der Kaufbeleg ersetzt keine Spendenbescheinigung im Sinne des Steuerrechts.',
                'donationTitle'             => 'Spende via elefunds',
                'acceptAsPaymentProvider'   => 'elefunds bei %s einblenden',
                'decimalSeparator'          => ','
            ),
            'en'    =>  array(
                'additionalInvoiceText'     => 'Your donation is processed by the elefunds Foundation gUG which forwards 100% to your chosen charities.',
                'donationTitle'             => 'Donation via elefunds',
                'acceptAsPaymentProvider'   => 'Enable elefunds with %s',
                'decimalSeparator'          => '.'
            )
        );
    }

    /**
     * Returns the localized string for a given key.
     *
     * @param string $value
     * @param string $languageCode (uses Shopware's detected language if not given)
     * @return string
     */
    public static function getLocale($value, $languageCode = NULL) {
        if (self::$locale === NULL) {
            self::init();
        }

        $languageCode = $languageCode === NULL ? self::getLanguage() : $languageCode;

        return self::$locale[$languageCode][$value];
    }

    /**
     * Loads a localized file.
     *
     * @param string $filename
     * @param bool $prefixWithLanguageCode
     * @param string $languageCode
     *
     * @return string
     */
    public static function loadFromFile($filename, $prefixWithLanguageCode = TRUE, $languageCode = NULL) {

        $languageCode = $languageCode === NULL ? self::getLanguage() : $languageCode;

        if ($prefixWithLanguageCode) {
            $filename = $languageCode . '_' . $filename;
        }

        $fullyQualifiedFilename = dirname(__FILE__) . DIRECTORY_SEPARATOR . $filename;

        if (is_readable($fullyQualifiedFilename)) {
            return file_get_contents($fullyQualifiedFilename);
        }
        return '';
    }
}