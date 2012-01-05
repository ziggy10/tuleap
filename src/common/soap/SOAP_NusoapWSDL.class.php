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

require_once 'nusoap.php';
require_once 'SOAP_WSDLGenerator.class.php';

class SOAP_NusoapWSDL {
    private $className;
    private $serviceName;
    private $uri;
    
    public function __construct($className, $serviceName, $uri) {
        $this->className   = $className;
        $this->serviceName = $serviceName;
        $this->uri         = $uri;
    }
    
    public function dumpWSDL() {
        // Instantiate server object
        $server = new soap_server();
        $server->configureWSDL($this->serviceName, $this->uri, false, 'rpc', 'http://schemas.xmlsoap.org/soap/http', $this->uri);
    
        $this->appendMethods($server);
        
        // Call the service method to initiate the transaction and send the response
        $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
        $server->service($HTTP_RAW_POST_DATA);
    }
    
    private function appendMethods(soap_server $server) {
        $reflection = new ReflectionClass($this->className);
        $wsdlGen    = new SOAP_WSDLGenerator($reflection);
        
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $methodName = $method->getName();
            $server->register(
                $methodName,
                $wsdlGen->getParams($methodName),
                $wsdlGen->getReturnType($methodName),
                $this->uri,
                $this->uri.'#'.$methodName,
                'rpc',
                'encoded',
                $wsdlGen->getComment($methodName)
            );
        }
    }
}

?>
