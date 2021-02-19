<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\App;
use Kavenegar\KavenegarApi;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function UploadFile($file_param, $path)
    {

        $fileName = $file_param->getClientOriginalName();
        $fileName = substr($fileName, 0, strrpos($fileName, '.'));
        $fileName = str_replace(' ', '', $fileName);
        $Random_Number = rand(0, 9999);
        $name_file_new = $fileName . '-' . $Random_Number . '.' . $file_param->guessClientExtension();
        $destinationPath = $path;
        $file_param->move($destinationPath, $name_file_new);
        $path_file = $path . $name_file_new;

        return $path_file;

    }

    public function SendSmsToUser($mobile, $message)
    {
        $SMSHandler = new KavenegarApi('325A77523867687576347446426D6A69332B2F706B664B38476F6D6F6C4C38354B714B326D333078616E493D');
        try {
            $SMSHandler->Send('10008663', $mobile, $message); /*$mobil:شماره ای که قرار هست پیامک به آن شماره ارسال گردد*/
            /*چون از Send استفاده کردیم فقط به شماره ای که ت کاوه نگار ثبت کردیم میتوانیم پیامک بفرستیم*/
            return true;
        } catch (\Kavenegar\Exceptions\ApiException $e) {
            return false;
        }
    }

    public function SendLookUp($mobile, $code)
    {
        $SMSHandler = new KavenegarApi('325A77523867687576347446426D6A69332B2F706B664B38476F6D6F6C4C38354B714B326D333078616E493D');
        try {
            $SMSHandler->VerifyLookup($mobile, $code,'','','VerifyUser');
            return true;
        } catch (\Kavenegar\Exceptions\ApiException $e) {
            return false;
        }
    }

}
