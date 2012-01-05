<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class SOAP_WSDLGenerator {
    /**
     * @var ReflectionClass
     */
    private $reflection;
    
    public function __construct(ReflectionClass $reflection) {
        $this->reflection = $reflection;
    }
    
    public function getComment($methodName) {
        $comment = '';
        foreach ($this->getCommentLines($methodName) as $line) {
            $line = trim($line);
            $line = preg_replace('%^/\*\*%', '', $line);
            $line = preg_replace('%^\*/%', '', $line);
            $line = preg_replace('%^\*%', '', $line);

            if (strpos($line, '@param') !== false || strpos($line, '@return') !== false || strpos($line, '@see') !== false) {
                continue;
            }

            $comment .= trim($line).PHP_EOL;
        }
        return $comment;
    }
    
    private function getCommentLines($methodName) {
        $method     = $this->reflection->getMethod($methodName);
        $method->getDocComment();
        return explode(PHP_EOL, $method->getDocComment());
    }
    
    public function getParams($methodName) {
        $params = array();
        foreach ($this->getCommentLines($methodName) as $line) {
            $matches = array();
            if (preg_match('%@param[ \t]+([^ \t]*)[ \t]+([^ \t]*)[ \t]+.*%', $line, $matches)) {
                $params[$this->docParamToSoap($matches[2])] = $this->docTypeToSoap($matches[1]);
            }
        }
        return $params;
    }
    
    private function docParamToSoap($paramName) {
        return substr($paramName, 1);
    }
    
    private function docTypeToSoap($docType) {
        switch(strtolower($docType)) {
            case 'string':
                return 'xsd:string';
            case 'integer':
            case 'int':
                return 'xsd:int';
            case 'boolean':
            case 'bool':
                return 'xsd:boolean';
        }
    }
    
    public function getReturnType($methodName) {
        $params = array();
        foreach ($this->getCommentLines($methodName) as $line) {
            $matches = array();
            if (preg_match('%@return[ \t]+([^ \t]*)%', $line, $matches)) {
                return array($methodName => $this->docTypeToSoap($matches[1]));
            }
        }
    }

}

?>
