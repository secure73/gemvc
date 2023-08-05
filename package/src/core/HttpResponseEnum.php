<?php

enum HttpResponseEnum: int
{
    case SUCCESS = 200;
    
    case CREATED = 201;
 
    case NO_CONTENT = 204;

    case UPDATED = 209;

    case DELETED = 210;

    case UNAUTHORIZED = 401;

    case FORBIDDEN = 403;

    case NOT_FOUND = 404;

    case INTERNAL_SERVER_ERROR = 500;

    case BAD_REQUEST = 400;
}
