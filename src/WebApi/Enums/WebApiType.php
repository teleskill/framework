<?php

namespace Teleskill\Framework\WebApi\Enums;

enum WebApiType {
    case NONE;
    case RAW_JSON;
    case RAW_TEXT;
    case MULTIPART;
    case FORM_PARAMS;
}