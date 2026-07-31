<?php

namespace app\controller;

use library\mysmarty\Controller;

class Index extends Controller
{
    public function index(): void
    {
        $this->assign('name', '果果开发');
        $this->display();
    }
}