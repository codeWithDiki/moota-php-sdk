<?php

namespace Moota\Moota\Domain;

use Illuminate\Support\Facades\Config;
use Moota\Moota\Config\Moota;
use Moota\Moota\DTO\Topup\CreateTopupData;
use Moota\Moota\DTO\Topup\ManualConfirmationTopupData;
use Moota\Moota\Exception\MootaException;
use Moota\Moota\Helper\Helper;
use Moota\Moota\Response\ParseResponse;
use Moota\Moota\Response\Topup\TopupResponse;
use Zttp\Zttp;

class Topup
{

    /**
     * Get list payment method | targeting bank account topup point
     *
     * @return void
     * @throws MootaException
     */
    public function getPaymentMethod()
    {
        $url = Moota::BASE_URL . Moota::ENDPOINT_PAYMENT_METHOD;

        return (new ParseResponse(
            Zttp::withHeaders([
                'User-Agent'        => 'Moota/2.0',
                'Accept'            => 'application/json',
                'Authorization'     => 'Bearer ' . Moota::$ACCESS_TOKEN
            ])->get($url), $url
        ))
            ->getResponse();
    }

    /**
     * Get List Amounts Point | minimum and maximum point can topup
     *
     * @return void
     * @throws MootaException
     */
    public function getListAmountPoint()
    {
        $url = Moota::BASE_URL . Moota::ENDPOINT_TOPUP_DENOM;

        return (new ParseResponse(
            Zttp::withHeaders([
                'User-Agent'        => 'Moota/2.0',
                'Accept'            => 'application/json',
                'Authorization'     => 'Bearer ' . Moota::$ACCESS_TOKEN
            ])->get($url), $url
        ))
            ->getResponse();
    }

    /**
     * Get list topup
     *
     * @param int $page
     *
     * @return TopupResponse
     * @throws MootaException
     */
    public function getListTopupPoint(int $page = 1): TopupResponse
    {
        $url = Moota::BASE_URL . Moota::ENDPOINT_TOPUP_INDEX;

        return (new ParseResponse(
            Zttp::withHeaders([
                'User-Agent'        => 'Moota/2.0',
                'Accept'            => 'application/json',
                'Authorization'     => 'Bearer ' . Moota::$ACCESS_TOKEN
            ])->get($url, ['page' => $page]), $url
        ))
        ->getResponse()
        ->getTopupData();
    }

    /**
     * Create Topup Point
     *
     * @param CreateTopupData $createTopupData
     * @return mixed
     * @throws MootaException
     */
    public function createTopupPoint(CreateTopupData $createTopupData): mixed
    {
        $url = Moota::BASE_URL . Moota::ENDPOINT_TOPUP_STORE;

        return (new ParseResponse(
            Zttp::withHeaders([
                'User-Agent'        => 'Moota/2.0',
                'Accept'            => 'application/json',
                'Authorization'     => 'Bearer ' . Moota::$ACCESS_TOKEN
            ])->post($url, $createTopupData->toArray()), $url
        ))
            ->getResponse()
            ->getTopupData();
    }

    public function uploadFileTopupPointManualConfirmation(ManualConfirmationTopupData $manualConfirmationTopupData)
    {
        $url = Helper::replace_uri_with_id(Moota::BASE_URL . Moota::ENDPOINT_TOPUP_CONFIRMATION, $manualConfirmationTopupData->topup_id, '{topup_id}');
        return (new ParseResponse(
            Zttp::asMultipart()->withHeaders([
                'User-Agent'        => 'Moota/2.0',
                'Accept'            => 'application/json',
                'Content-Type'      => 'multipart/form-data',
                'Authorization'     => 'Bearer ' . Moota::$ACCESS_TOKEN
            ])->post($url,
                [
                    [
                        'name'     => 'file',
                        'contents' => 'abc'
                    ],
                    [
                        'name'     => 'logo-icon',
                        'contents' => 'qux',
                    ],
                    [
                        'name' => 'logo-icon',
                        'contents' => 'test contents',
                        'filename' => $manualConfirmationTopupData->file,
                    ],
                ]
            ), $url
        ))
            ->getResponse();
    }


}