<?php
/**
 * Author: thomasklaner
 * Date: 26/02/15
 * Time: 14:21
 */

namespace Http;

/**
 * Class RequestParameters
 * Simple class to ease and validate request parameters
 */
class RequestParameters {

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
     */
    protected function setConstraints(array $constraints) {
        foreach($constraints as $name => $constraint) {
            if(!$this->has($name)) {
                $required = @$constraint['required'] ?: false;
                if($required) {
                    die('Required request parameter "' . $name . '" was not specified!');
                }
                if(array_key_exists('default', $constraint)) {
                    $this->params[$name] = $constraint['default'];
                }
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
