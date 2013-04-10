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

namespace Shopware\CustomModels\Elefunds\Donation;

use Shopware\Components\Model\ModelEntity,
    Doctrine\ORM\Mapping AS ORM,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * A doctrine representation of an elefunds donation.
 *
 * @package    elefunds Shopware Module
 * @subpackage Models
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 1.0.0
 *
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\Table(name="s_plugin_elefunds_donation")
 * @ORM\HasLifecycleCallbacks
 */
class Donation extends ModelEntity
{

    /**
     * Donation states
     */
    const SCHEDULED_FOR_ADDING          = 0,
          SCHEDULED_FOR_CANCELLATION    = 1,
          SCHEDULED_FOR_VERIFICATION    = 2,
          PENDING                       = 3,
          CANCELLED                     = 4,
          VERIFIED                      = 5;

    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="foreign_id", type="string",  length=255, nullable=false)
     */
    private $foreignId;

    /**
     * @var int
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    private $amount;

    /**
     * @var int
     * @ORM\Column(name="suggested_amount", type="integer", nullable=false)
     */
    private $suggestedAmount;

    /**
     * @var int
     * @ORM\Column(name="grand_total", type="integer", nullable=false)
     */
    private $grandTotal;

    /**
     * Coma separated list of receivers.
     *
     * @var string
     * @ORM\Column(name="receiver_ids", type="string", length=255, nullable=false)
     */
    private $receiverIds;

    /**
     * Coma separated list of receivers.
     *
     * @var string
     * @ORM\Column(name="available_receiver_ids", type="string", length=255, nullable=false)
     */
    private $availableReceiverIds;

    /**
     * @var \DateTime
     * @ORM\Column(name="time", type="datetime", nullable=false)
     */
    private $time;

    /**
     * @var string
     * @ORM\Column(name="donator_email", type="string", length=255, nullable=true)
     */
    private $donatorEmail;

    /**
     * @var string
     * @ORM\Column(name="donator_first_name", type="string", length=255, nullable=true)
     */
    private $donatorFirstName;

    /**
     * @var string
     * @ORM\Column(name="donator_last_name", type="string", length=255, nullable=true)
     */
    private $donatorLastName;

    /**
     * @var string
     * @ORM\Column(name="donator_street_address", type="string", length=255, nullable=true)
     */
    private $donatorStreetAddress;

    /**
     * @var int
     * @ORM\Column(name="donator_zip", type="integer", nullable=true)
     */
    private $donatorZip;

    /**
     * @var string
     * @ORM\Column(name="donator_city", type="string", length=255, nullable=true)
     */
    private $donatorCity;

    /**
     * @var string
     * @ORM\Column(name="donator_countrycode", type="string", length=2, nullable=true)
     */
    private $donatorCountrycode;

    /**
     * @var int
     * @ORM\Column(name="state", type="integer", nullable=false),
     */
    private $state = 0;


    /**
     * Sets the reporting state.
     *
     * @param int $state
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setState($state) {
        $this->state = $state;
        return $this;
    }

    /**
     * Retrieves the reporting state.
     *
     * @return int
     */
    public function getState() {
        return $this->state;
    }



    /**
     * Sets the amount in cent.
     *
     * @param int $amount
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setAmount($amount) {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Returns the amount in cent.
     *
     * @return int
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * Sets the available receivers, that are saved as CSV in
     * the database.
     *
     * @param array $availableReceiverIds
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setAvailableReceiverIds($availableReceiverIds) {
        $this->availableReceiverIds = implode(',', $availableReceiverIds);
        return $this;
    }

    /**
     * Returns the CSV from the database mapped to an array of integers and
     * returns it.
     *
     * @return array
     */
    public function getAvailableReceiverIds() {
        return array_map(function($x) { return (int)$x;}, explode(',',$this->availableReceiverIds));
    }

    /**
     * Sets the city of the donator.
     *
     * @param string $donatorCity
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setDonatorCity($donatorCity) {
        $this->donatorCity = $donatorCity;
        return $this;
    }

    /**
     * Returns the city of the donator.
     *
     * @return string
     */
    public function getDonatorCity() {
        return $this->donatorCity;
    }

    /**
     * Sets the countrycode / language code of the donator.
     *
     * @param string $donatorCountrycode
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setDonatorCountrycode($donatorCountrycode) {
        $this->donatorCountrycode = $donatorCountrycode;
        return $this;
    }

    /**
     * Returns the countrycode of the donator.
     *
     * @return string
     */
    public function getDonatorCountrycode() {
        return $this->donatorCountrycode;
    }

    /**
     * Sets the donator email.
     *
     * Validation of the email should be done prior to the setter.
     *
     * @param string $donatorEmail
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setDonatorEmail($donatorEmail) {
        $this->donatorEmail = $donatorEmail;
        return $this;
    }

    /**
     * Returns the donator's email
     *
     * @return string
     */
    public function getDonatorEmail() {
        return $this->donatorEmail;
    }

    /**
     * Sets the donator's first name
     *
     * @param string $donatorFirstName
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setDonatorFirstName($donatorFirstName) {
        $this->donatorFirstName = $donatorFirstName;
        return $this;
    }

    /**
     * Returns the donator's first name
     *
     * @return string
     */
    public function getDonatorFirstName() {
        return $this->donatorFirstName;
    }

    /**
     * Sets the donator's last name
     *
     * @param string $donatorLastName
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setDonatorLastName($donatorLastName) {
        $this->donatorLastName = $donatorLastName;
        return $this;
    }

    /**
     * Returns the donator's last name
     *
     * @return string
     */
    public function getDonatorLastName() {
        return $this->donatorLastName;
    }

    /**
     * Sets the donator's street address.
     *
     * @param string $donatorStreetAddress
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setDonatorStreetAddress($donatorStreetAddress) {
        $this->donatorStreetAddress = $donatorStreetAddress;
        return $this;
    }

    /**
     * Returns the donator's street address.
     *
     * @return string
     */
    public function getDonatorStreetAddress() {
        return $this->donatorStreetAddress;
    }

    /**
     * Sets the zip code of the donator.
     *
     * @param int $donatorZip
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setDonatorZip($donatorZip) {
        $this->donatorZip = $donatorZip;
        return $this;
    }

    /**
     * Returns the zip code of the donator.
     *
     * @return int
     */
    public function getDonatorZip() {
        return $this->donatorZip;
    }

    /**
     * Sets the foreignId, preferably the order id.
     *
     * @param string $foreignId
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setForeignId($foreignId) {
        $this->foreignId = $foreignId;
        return $this;
    }

    /**
     * Returns the foreignId of the donation.
     *
     * @return string
     */
    public function getForeignId() {
        return $this->foreignId;
    }

    /**
     * Sets the grand total, prior to the roundup.
     *
     * @param int $grandTotal
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setGrandTotal($grandTotal) {
        $this->grandTotal = $grandTotal;
        return $this;
    }

    /**
     * Returns the grand total, prior to the roundup.
     *
     * @return int
     */
    public function getGrandTotal() {
        return $this->grandTotal;
    }

    /**
     * Sets all receivers and maps them to a csv for the database.
     *
     * @param array $receiverIds
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setReceiverIds($receiverIds) {
        $this->receiverIds = implode(',', $receiverIds);
        return $this;
    }

    /**
     * Maps back the csv to an array of receiver ids as int and
     * returns it.
     *
     * @return array
     */
    public function getReceiverIds() {
        return array_map(function($x) { return (int)$x;}, explode(',',$this->receiverIds));
    }

    /**
     * Sets the suggested amount in cent.
     *
     * @param int $suggestedAmount
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setSuggestedAmount($suggestedAmount) {
        $this->suggestedAmount = $suggestedAmount;
        return $this;
    }

    /**
     * Returns the suggested amount in cent.
     *
     * @return int
     */
    public function getSuggestedAmount() {
        return $this->suggestedAmount;
    }

    /**
     * Sets the datetime that represents the timestamp of the made
     * donation.
     *
     * @param \DateTime $time
     * @return \Shopware\CustomModels\Elefunds\Donation\Donation
     */
    public function setTime($time) {
        $this->time = $time;
        return $this;
    }

    /**
     * Returns the datetime that represents the timestamp of the made
     * donation.
     *
     * @return \DateTime
     */
    public function getTime() {
        return $this->time;
    }
}
