<?php
namespace Ormurin\Hull\Engine;

use Ormurin\Hull\Tackle\Config;

class Controller
{
    protected Request $request;
    protected Config $config;
    protected array $options;
    protected array $params;

    public function __construct(?Request $request = null, array $params = [], array $options = [], Config|array $config = [])
    {
        if ( is_array($config) ) {
            $config = new Config($config);
        }
        if ( !$request ) {
            $request = Env::instance()->getRequest();
        }
        $this->request = $request;
        $this->options = $options;
        $this->params = $params;
        $this->config = $config;
        $this->_init();
    }

    protected function _init(): void
    {

    }


}
