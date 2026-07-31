<?php

namespace library\enum;

enum Captcha
{
    case NUMBER_AND_LETTER;
    case NUMBER;
    case LETTER;
    case ZH;
}