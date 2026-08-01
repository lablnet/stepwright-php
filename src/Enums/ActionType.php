<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Enums;

enum ActionType: string
{
    case Navigate = 'navigate';
    case Input = 'input';
    case Click = 'click';
    case Data = 'data';
    case Scroll = 'scroll';
    case EventBaseDownload = 'eventBaseDownload';
    case Foreach = 'foreach';
    case Open = 'open';
    case SavePDF = 'savePDF';
    case PrintToPDF = 'printToPDF';
    case DownloadPDF = 'downloadPDF';
    case DownloadFile = 'downloadFile';
    case Reload = 'reload';
    case GetUrl = 'getUrl';
    case GetTitle = 'getTitle';
    case GetMeta = 'getMeta';
    case GetCookies = 'getCookies';
    case SetCookies = 'setCookies';
    case GetLocalStorage = 'getLocalStorage';
    case SetLocalStorage = 'setLocalStorage';
    case GetSessionStorage = 'getSessionStorage';
    case SetSessionStorage = 'setSessionStorage';
    case GetViewportSize = 'getViewportSize';
    case SetViewportSize = 'setViewportSize';
    case Screenshot = 'screenshot';
    case WaitForSelector = 'waitForSelector';
    case Evaluate = 'evaluate';
    case Hover = 'hover';
    case DragAndDrop = 'dragAndDrop';
    case Select = 'select';
    case UploadFile = 'uploadFile';
    case VirtualScroll = 'virtualScroll';
    case ReadData = 'readData';
    case WriteData = 'writeData';
    case Custom = 'custom';

    public static function tryFromString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }
}
