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

namespace Shopware\CustomModels\Elefunds\Receiver;

use Shopware\Components\Model\ModelEntity,
    Doctrine\ORM\Mapping AS ORM,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * A doctrine representation of an elefunds receiver.
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
 * @ORM\Table(name="s_plugin_elefunds_receiver")
 * @ORM\HasLifecycleCallbacks
 */
class Receiver extends ModelEntity
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $internalIdentifier;

    /**
     * @var int
     * @ORM\Column(name="receiver_id", type="integer", nullable=false)
     */
    protected $receiverId;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=128, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="countrycode", type="string", length=2, nullable=false)
     */
    protected $countrycode;

    /**
     * @var string
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected $description;

    /**
     * @var string
     * @ORM\Column(name="image_url", type="string", length=255, nullable=false)
     */
    protected $imageUrl;

    /**
     * @var \DateTime
     * @ORM\Column(name="valid", type="datetime", nullable=false)
     */
    protected $valid;

    /**
     * Returns the receiver id (not the id of the database row).
     *
     * @return int
     */
    public function getId() {
        return $this->receiverId;
    }

    /**
     * Sets the receiver id (not the id of the database row).
     * @param int $receiverId
     * @return Receiver
     */
    public function setId($receiverId) {
        $this->receiverId = $receiverId;
        return $this;
    }

    /**
     * Sets the countrycode / language code of the receiver.
     *
     * @param string $countrycode
     * @return Receiver
     */
    public function setCountrycode($countrycode) {
        $this->countrycode = $countrycode;
        return $this;
    }

    /**
     * Returns the countrycode / language code of the receiver.
     *
     * @return int
     */
    public function getCountrycode() {
        return $this->countrycode;
}

    /**
     * Sets the image url for the receiver logo.
     *
     * @param string $imageUrl
     * @return Receiver
     */
    public function setImage($imageUrl) {
        $this->imageUrl = $imageUrl;
        return $this;
}

    /**
     * Returns the image url for the receiver logo.
     *
     * @return string
     */
    public function getImage() {
        return $this->imageUrl;
}

    /**
     * Sets the name of the receiver.
     *
     * @param string $name
     * @return Receiver
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
}

    /**
     * Returns the name of the receiver.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets the description of the receiver.
     *
     * @param string $description
     * @return \Shopware\CustomModels\Elefunds\Receiver\Receiver
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Returns the description of the receiver.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * Sets the valid time until when the receiver is valid.
     *
     * @param \DateTime $valid
     * @return Receiver
     */
    public function setValid($valid) {
        $this->valid = $valid;
        return $this;
    }

    /**
     * Returns the valid time until when the receiver is valid.
     *
     * @return \DateTime
     */
    public function getValid() {
        return $this->valid;
    }

}

