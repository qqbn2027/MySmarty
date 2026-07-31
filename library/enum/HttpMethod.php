<?php

namespace library\enum;
enum HttpMethod
{
    case get;
    case post;
    case put;
    case delete;
    case head;
    case patch;
    case options;
    case request;
    case files;
    case cookie;
    case session;
    case env;
    case server;
}