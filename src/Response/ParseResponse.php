<?php


namespace Moota\Moota\Response;

use Moota\Moota\Config\Moota;
use Moota\Moota\Exception\MootaException;

class ParseResponse
{
    public $responseClass = [
        Moota::ENDPOINT_MUTATION_INDEX => 'Moota\Moota\Response\MutationResponse',
        Moota::ENDPOINT_MUTATION_STORE => 'Moota\Moota\Response\MutationResponse',

        Moota::ENDPOINT_BANK_INDEX => 'Moota\Moota\Response\BankAccount\BankAccountResponse',
        Moota::ENDPOINT_BANK_STORE => 'Moota\Moota\Response\BankAccount\BankAccountResponse',
        Moota::ENDPOINT_BANK_UPDATE => 'Moota\Moota\Response\BankAccount\BankAccountResponse',

        Moota::ENDPOINT_TAGGING_STORE => 'Moota\Moota\Response\Tagging\TaggingResponse',

        Moota::ENDPOINT_TOPUP_INDEX => 'Moota\Moota\Response\Topup\TopupResponse',
    ];

    public $exceptionClass = [
        Moota::ENDPOINT_MUTATION_INDEX => 'Moota\Moota\Exception\Mutation\MutationException',
        Moota::ENDPOINT_MUTATION_STORE =>  'Moota\Moota\Exception\Mutation\MutationException',
        Moota::ENDPOINT_MUTATION_DESTROY => 'Moota\Moota\Exception\Mutation\MutationException'
    ];

    private $response;

    public function __construct($results, $url)
    {
        $parts = parse_url($url);

        if(! $results->isOk() ) {
            if( isset($this->exceptionClass[$parts['path']]) ) {
                throw new $this->exceptionClass[$parts['path']]($results->json()['message'], $results->status(), null, $results->json());
            }

            throw new MootaException($results->json()['message'], $results->status(), null, $results->json());
        }

        if(! isset($this->responseClass[$parts['path']])) {
            return $this->response = $results->json();
        }

        $this->response = new $this->responseClass[$parts['path']]($results->json());
    }

    /**
     * Get response following by class
     *
     */
    public function getResponse()
    {
        return $this->response;
    }
}