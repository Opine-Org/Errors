<?php
/**
 * Opine\Errors
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Opine;

class Errors {
    private $topic;
    private $messages = [];
    private $config;
    private $separation;

    public function __construct ($config, $topic, $separation) {
        $this->topic = $topic;
        $this->config = $config;
        $this->separation = $separation;
    }

    public function check () {
        if (count($this->messages) > 0) {
            return false;
        }
        return true;
    }

    public function push ($message) {
        $this->messages[] = $message;
    }

    public function exception ($e) {
        $project = $this->config->project;
        $root = (($sapi == 'cli') ? getcwd() : $_SERVER['DOCUMENT_ROOT']);
        $context = [
            'code'          => uniqid(),
            'root'          => $root,
            'created_date'  => date('Y-m-d H:i:s'),
            'message'       => $e->getMessage(),
            'file'          => $e->getFile(),
            'line'          => $e->getLine(),
            'stack'         => (array)$e->getTrace(),
            'get'           => (isset($_GET) ? $_GET : []),
            'post'          => (isset($_POST) ? $_POST : []),
            'session'       => (isset($_SESSION) ? $_SESSION : []),
            'server'        => (isset($_SERVER) ? $_SERVER : [])
        ];
        $topic->publish('exception', $context);
        $mode = 'production';
        if (isset($project['mode'])) {
            $mode = $project['mode'];
        }
        if ($mode == 'development' || php_sapi_name() == 'cli') {
            print_r($context);
            exit;
        }
        $this->separation->app('app/errors')->layout('errors-' . $mode)->data('errors', $context)->write();
        exit;
    }

    public function get () {
        return $this->messages;
    }
}