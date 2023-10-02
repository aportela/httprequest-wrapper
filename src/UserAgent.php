<?php

namespace aportela\HTTPRequestWrapper;

enum UserAgent: string
{
    case DEFAULT = "HTTPRequest-Wrapper - https://github.com/aportela/httprequest-wrapper (766f6964+github@gmail.com)";
    case CHROME_WINDOWS_10 = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36";
    case FIREFOX_WINDOWS_10 = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/118.0";
}
