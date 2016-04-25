<?php
/**
 * Author: thomasklaner
 * Date: 26/02/15
 * Time: 14:21
 */

namespace Imagex\Http;

/**
 * Class RequestParameters
 * Simple class to ease and validate request parameters
 */
class RequestParameters {

    const TYPE_URL = 'url';
    const TYPE_BOOL = 'bool';

    /** @var array */
    protected $constraints;

    /** @var array */
    protected $params;

    public function __construct(array $params = null, array $constraints = array()) {
        $this->params = $params ?: $_REQUEST;
        $this->setConstraints($constraints);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name) {
        return array_key_exists($name, $this->params) && !empty(trim($this->get($name)));
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get($name) {
        return $this->__get($name);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return @$this->params[$name];
    }

    /**
     * @param array $constraints
     * @throws RequestParameterException
     */
    protected function setConstraints(array $constraints) {
        foreach($constraints as $name => $constraint) {
            if(!$this->has($name)) {
                $required = @$constraint['required'] ?: false;
                if($required) {
                    throw new RequestParameterException('Required request parameter "' . $name . '" was not specified!');
                }
                if(array_key_exists('default', $constraint)) {
                    $this->params[$name] = $constraint['default'];
                }
            }
            $type = @$constraint['type'] ?: 'undefined';
            switch($type) {
                case self::TYPE_URL:
                    // Allow base64 encoded URLs as well
                    if(strpos($this->params[$name], 'http') !== 0) {
                        $decoded = base64_decode(str_replace(' ','+',$this->params[$name]), true);
                        if($decoded !== false) {
                            $this->params[$name] = $decoded;
                        } else {
                            throw new RequestParameterException('Request parameter "' . $name . '" is not a valid URL!');
                        }
                    }
                    break;
                case self::TYPE_BOOL:
                    // ensure param is bool
                    $value = $this->params[$name];
                    $this->params[$name] = in_array($value, [1, '1', true, 'true'], true);
                    break;
            }
        }
        $this->constraints = $constraints;
    }

    /**
     * @return string
     */
    public function getHash() {
        $hashParams = array();
        foreach($this->constraints as $name => $constraint) {
            $hashParams[] = $this->get($name);
        }
        return md5(join('|', $hashParams));
    }
}

class RequestParameterException extends \Exception {}
