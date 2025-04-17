<?php
namespace Unit\Core\Controller;

use Ormurin\Hull\Engine\Controller;

class IndexController extends Controller
{
    public function index(): string
    {
        return $this->view->render('~/index/index.phtml');
    }
}
