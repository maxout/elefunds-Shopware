<?php

/**
 * elefunds API PHP Library
 *
 * Copyright (c) 2012 - 2013, elefunds GmbH <contact@elefunds>.
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
 *
 */

namespace Lfnds\Communication;

use Lfnds\Exception\ElefundsCommunicationException;

require_once __DIR__ . '/../Exception/ElefundsCommunicationException.php';

/**
 * Rest Interface
 *
 * By default, the API uses the CurlRequest implementation of this interface.
 * If you are not able (or not want to) use curl, use this interface for your request method.
 *
 * @package    elefunds API PHP Library
 * @subpackage Communication
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 - 2013 elefunds GmbH <contact@elefunds>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 1.0.0
 */
interface RestInterface  {

    /**
     * Sets the user-agent that identifies the request.
     *
     * @param string $agent
     * @return $this
     */
    public function setUserAgent($agent);

    /**
     * Performs a GET Request against a given URL.
     *
     * @param string $restUrl with fully qualified resource path
     * @throws ElefundsCommunicationException if connection or authentication fails or retrieved http code is not 200
     * @return string the server response as JSON
     */
    public function get($restUrl);

    /**
     * Performs a POST Request against a given URL.
     *
     * @param string $restUrl with fully qualified resource path
     * @param string $body the JSON body
     * @throws ElefundsCommunicationException if connection or authentication fails or retrieved http code is not 200
     * @return string the server response as JSON
     */
    public function post($restUrl, $body);

    /**
     * Performs a PUT Request against a given URL.
     *
     * @param string $restUrl with fully qualified resource path
     * @param string $body the JSON body
     * @throws ElefundsCommunicationException if connection or authentication fails or retrieved http code is not 200
     * @return string the server response as JSON
     */
    public function put($restUrl, $body = '');

    /**
     * Performs a DELETE Request against a given URL.
     *
     * @param string $restUrl with fully qualified resource path
     * @throws ElefundsCommunicationException if connection or authentication fails or retrieved http code is not 200
     * @return string the server response as JSON
     */
    public function delete($restUrl);


}