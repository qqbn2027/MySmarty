<?php

namespace app\controller;

use library\mysmarty\Controller;
use library\mysmarty\Route;

class Index extends Controller
{
    #[Route(home: true)]
    public function index(): void
    {
        $this->assign('name', '果果开发');
        $this->display();
    }
}