<?php


namespace Test\Domain;

use Moota\Moota\Config;
use Moota\Moota\Exception\Mutation\MootaException;
use Moota\Moota\Exception\Mutation\MutationException;
use Moota\Moota\Helper\Helper;
use Moota\Moota\ParseResponse;
use PHPUnit\Framework\TestCase;
use Test\server\ZttpServer;
use Zttp\Zttp;

class MutationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {

        ZttpServer::start();
    }

    function url($url)
    {
        return vsprintf('%s/%s', [
            'http://localhost:' . getenv('TEST_SERVER_PORT'),
            ltrim($url, '/'),
        ]);
    }

    public function testGetMutationResponse()
    {
        Config::$ACCESS_TOKEN = 'abcdefghijklmnopqrstuvwxyz';
        $params = [
            'type'          => 'CR',
            'bank'          => 'klasdoi',
            'amount'        => '100012',
            'description'   => 'Test Mutations',
            'note'          => '',
            'date'          => '',
            'start_date'    => '2021-09-22',
            'end_date'      => '2020-09-23',
            'tag'           => 'tag_1,tag_2',
            'page'          => 1,
            'per_page'      => 20
        ];

        $response = Zttp::withHeaders([
            'User-Agent'        => 'Moota/2.0',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . Config::$ACCESS_TOKEN
        ])
        ->get($this->url(Config::ENDPOINT_MUTATION_INDEX), $params);

        $this->assertTrue($response->status() === 200);
        $this->assertEquals(
            $response->json(),
            (new ParseResponse($response, Config::ENDPOINT_MUTATION_INDEX))->getResponse()->getData()
        );
    }

    public function testFailedGetMutationWithBankNotFound()
    {
        Config::$ACCESS_TOKEN = 'abcdefghijklmnopqrstuvwxyz';
        $params = [
            'type'          => 'CR',
            'bank'          => 1,
            'amount'        => '100012',
            'description'   => 'Test Mutations',
            'note'          => '',
            'date'          => '',
            'start_date'    => '2021-09-22',
            'end_date'      => '2020-09-23',
            'tag'           => 'tag_1,tag_2',
            'page'          => 1,
            'per_page'      => 20
        ];

        $response = Zttp::withHeaders([
            'User-Agent'        => 'Moota/2.0',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . Config::$ACCESS_TOKEN
        ])
            ->get($this->url(Config::ENDPOINT_MUTATION_INDEX), $params);

        $this->expectException(MutationException::class);
        $this->assertTrue($response->status() === 404);
        (new ParseResponse($response, Config::ENDPOINT_MUTATION_INDEX))->getResponse()->getData();
    }

    public function testStoreMutation()
    {
        Config::$ACCESS_TOKEN = 'abcdefghijklmnopqrstuvwxyz';
        $payload = [
          'date'    => '2021-09-21',
          'note'    => 'Testing Note Mutation',
          'amount'  => '2000123',
          'type'    => 'CR'
        ];

        $response = Zttp::withHeaders([
            'User-Agent'        => 'Moota/2.0',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . Config::$ACCESS_TOKEN
        ])
            ->post($this->url(Config::ENDPOINT_MUTATION_STORE), $payload);

        $this->assertTrue($response->status() === 200);
        $this->assertEquals([
            'error' => false,
            'mutation' => [
                'total' => 1,
                'new' => 1,
            ]
        ], (new ParseResponse($response, Config::ENDPOINT_MUTATION_STORE))->getResponse()->getData());
    }

    public function testFailedStoreMutation()
    {
        Config::$ACCESS_TOKEN = 'abcdefghijklmnopqrstuvwxyz';
        $payload = [
            'date'    => '2021-09-21',
            'note'    => 'Testing Note Mutation',
            'amount'  => '2000123',
            'type'    => ''
        ];

        $response = Zttp::withHeaders([
            'User-Agent'        => 'Moota/2.0',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . Config::$ACCESS_TOKEN
        ])
            ->post($this->url(Config::ENDPOINT_MUTATION_STORE), $payload);

        $this->assertTrue($response->status() === 422);
        $this->expectException(MutationException::class);
        (new ParseResponse($response, Config::ENDPOINT_MUTATION_STORE))->getResponse();
    }

    public function testAddNoteToMutation()
    {
        Config::$ACCESS_TOKEN = 'abcdefghijklmnopqrstuvwxyz';
        $payload = [
            'note'    => 'Testing Note Mutation',
        ];

        $response = Zttp::withHeaders([
            'User-Agent'        => 'Moota/2.0',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . Config::$ACCESS_TOKEN
        ])
            ->post($this->url(Helper::replace_uri_with_id(Config::ENDPOINT_MUTATION_NOTE, 'hash_mutation_id', '{mutation_id}')), $payload);

        $this->assertTrue($response->status() === 200);
        $this->assertEquals($response->json(), (new ParseResponse($response, Config::ENDPOINT_MUTATION_NOTE))->getResponse());
    }

    public function testFailAddNoteMutation()
    {
        Config::$ACCESS_TOKEN = 'abcdefghijklmnopqrstuvwxyz';
        $payload = [
            'note'    => 'Testing Note Mutation',
        ];

        $response = Zttp::withHeaders([
            'User-Agent'        => 'Moota/2.0',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . Config::$ACCESS_TOKEN
        ])
            ->post($this->url(Helper::replace_uri_with_id(Config::ENDPOINT_MUTATION_NOTE, 1, '{mutation_id}')), $payload);

        $this->assertNotTrue($response->status(), 200);
        $this->expectException(MootaException::class);
        $this->assertEquals($response->json(), (new ParseResponse($response, Config::ENDPOINT_MUTATION_NOTE))->getResponse());
    }

    public function testPushWebhookByWhenMutationId()
    {
        Config::$ACCESS_TOKEN = 'abcdefghijklmnopqrstuvwxyz';
        $mutation_id = "hashing_mutation_id";

        $response = Zttp::withHeaders([
            'User-Agent'        => 'Moota/2.0',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . Config::$ACCESS_TOKEN
        ])
            ->post($this->url(Helper::replace_uri_with_id(Config::ENDPOINT_MUTATION_PUSH_WEBHOOK, $mutation_id, '{mutation_id}')), []);

        $this->assertTrue($response->status() === 200);
        $this->assertEquals($response->json(), (new ParseResponse($response, Helper::replace_uri_with_id(Config::ENDPOINT_MUTATION_PUSH_WEBHOOK, $mutation_id, '{mutation_id}')))->getResponse());
    }

    public function testFailPushWebhookByWhenMutationIdNotFound()
    {
        Config::$ACCESS_TOKEN = 'abcdefghijklmnopqrstuvwxyz';
        $mutation_id = "abcd";

        $response = Zttp::withHeaders([
            'User-Agent'        => 'Moota/2.0',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . Config::$ACCESS_TOKEN
        ])
            ->post($this->url(Helper::replace_uri_with_id(Config::ENDPOINT_MUTATION_PUSH_WEBHOOK, $mutation_id, '{mutation_id}')), []);

        $this->expectException(MootaException::class);
        $this->assertTrue($response->status() === 404);
        $this->assertEquals($response->json(), (new ParseResponse($response, Helper::replace_uri_with_id(Config::ENDPOINT_MUTATION_PUSH_WEBHOOK, $mutation_id, '{mutation_id}')))->getResponse());
    }

    public function testdestroyMutation()
    {
        Config::$ACCESS_TOKEN = 'abcdefghijklmnopqrstuvwxyz';
        $mutation_ids['mutations'] = ["hash_mutation_id", "hash_mutation_id"];

        $response = Zttp::withHeaders([
            'User-Agent'        => 'Moota/2.0',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . Config::$ACCESS_TOKEN
        ])
            ->post($this->url(Config::ENDPOINT_MUTATION_DESTROY), $mutation_ids);

        $this->assertTrue($response->status() === 200);
        $this->assertEquals($response->json(), (new ParseResponse($response, Config::ENDPOINT_MUTATION_DESTROY))->getResponse());
    }

    public function testFaildestroyMutation()
    {
        Config::$ACCESS_TOKEN = 'abcdefghijklmnopqrstuvwxyz';
        $mutation_ids['mutations'] = ["abcdefg", "efgh"];

        $response = Zttp::withHeaders([
            'User-Agent'        => 'Moota/2.0',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . Config::$ACCESS_TOKEN
        ])
            ->post($this->url(Config::ENDPOINT_MUTATION_DESTROY), $mutation_ids);

        $this->assertTrue($response->status() === 500);
        $this->expectException(MutationException::class);
        $this->assertEquals($response->json(), (new ParseResponse($response, Config::ENDPOINT_MUTATION_DESTROY))->getResponse());
    }

    public function testFaildestroyMutationWithWrongRequestPayload()
    {
        Config::$ACCESS_TOKEN = 'abcdefghijklmnopqrstuvwxyz';
        $mutation_ids['mutations'] = [];

        $response = Zttp::withHeaders([
            'User-Agent'        => 'Moota/2.0',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . Config::$ACCESS_TOKEN
        ])
            ->post($this->url(Config::ENDPOINT_MUTATION_DESTROY), $mutation_ids);

        $this->assertTrue($response->status() === 422);
        $this->expectException(MutationException::class);
        $this->assertEquals($response->json(), (new ParseResponse($response, Config::ENDPOINT_MUTATION_DESTROY))->getResponse());
    }


}




//eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiJLWmZzZ200Z29kUiIsImp0aSI6IjQ0NTg3ZThiYzM4NTA1NzRiNWJlYjJjZmRlYTE3MmI3MDk1NWIxZjhkNTJhOTRlZWY4MGM5MTAzZjFmZjY5ZTk0NmVlNGQ0MjlhZTlhM2UyIiwiaWF0IjoxNjMyNDU2MzA1LjI0MDgyOSwibmJmIjoxNjMyNDU2MzA1LjI0MDgzMSwiZXhwIjoxNjYzOTkyMzA1LjIzNzA5Mywic3ViIjoiMSIsInNjb3BlcyI6WyJhcGkiXX0.UYJFOlnL4G4KZ2okxUwLNp2769u3O3k0Urhj84G9pU9lreq9L8-037wsWT79DHN-c1zUD1WBnoIEUS6o0q3aM5S-Ssi_roczmB-Ts2Yov_k02BqgW_oLRcbTarbbOhzzHyMHNP7vcI4QrYeyTzpJLo7Wd4Fn4jfhuqqFpLvW34QUxws3gUIQGVz8cKc12OmHlCLgN4N1Bz6hg5jaOMRycIiD03hGM4NazH4fMNDMddhKkbQw8QjnoNnNRnuf7bbLTR-LquyzLDVTT0YGMKH-Cbtu67hmYB9E2wwCimZUpUyM-Bir772BSi59nW66mPqslS1fO8IixlkWTH03hhNu_ninKFmCpH8ZTdkVnruCy9fPDsjAn2pXeSco3NTFnKQccpdCiCJzILyQMNSmSpicqJm3AgPIcdoIZH-La_7niSR5xlks4Ln9Tzu2y8rAgLBCrPK0rIaI9qnhMHX37HQgX7GS9auJM8QVVmePVmfCbG6qsNaoiv5NqJ6tvknK9ilhUNWrHpJvfDIIJYZOG8_sAILFY1DKWk-rm7AF2DiMeOVWIsyfXhmqgnQLDNHfxYjVNVsYO1VWKx2yT4x8ylnz82clGGSA8AO9Rfqj4sOHmxZjndfeW2EqNmHCLoMUFv6WtRLSX3Fjfz30JCX9_wKolMf642kBxdDIwBMx2xecP3Q

//        Config::$ACCESS_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiJmMktXRFJGeThpVSIsImp0aSI6ImIwNjE2MjgzNTFiZGU1ZWFjNmE3MDMwNjAxMzVhMTVlOGVhNzg5N2Q1ZTA2ZTg0NDNmNTAxYWYyMzJlMzIxMjg3MmU2ZjE4NGQ1ZTQ4MzMzIiwiaWF0IjoxNjMyMjk3MTE5LjAwMDg3MywibmJmIjoxNjMyMjk3MTE5LjAwMDg3NiwiZXhwIjoxNjYzODMzMTE4Ljk4ODYwMiwic3ViIjoiMSIsInNjb3BlcyI6WyJhcGkiXX0.p7y_paR0mFxlePH8iUK0BHjD3K2oGq_WYzbu660lENkht1wbSYEvxKR5_OnrQCIscSJefg-BS2gBxrHIQnI_5UtaiH8xNU8J6-IyRAgWsaJK2XpztFUg3ngXQ4DCQ5TRV7lNNt27GNhArZADxLzG6c1CbURUFlA8lnJhu5r1SCVoNI3DkAfwUH4SFa0V9cLDQb-7PK7liZ9T_V8EquXIuoLTZi-wkf5kwdbVg5OQV3P7Nx7O_7-7xYy9zrHySk2FBIqC-wmq22q4mZCOxjlIFhShkR7HxKpX0D_VzspHJYoGIpqd6eveSNDgu1AcuSM6WcR_vTl8iDxw0MVKHxwqjcZh6UdFWTNxs5my0rtoc3NT0XVr5M5ja1xEOqsI3kXi4FRDObD7gJxOp_FezmkQV3zbon7C3oUovKyxgfSssasTKwTJPpPg8z0XzsnbPEBtCM1QJruhyKcgZQyqXYdZzjVZ0FUuEBXgh8XBRrgbkrbqVKBTeRfylSefubawWLCxkrnIwbR3YdMEhJK18nR1IUH8S1jJ8dOWO3l5NX-gJpVBEFkL2R7iLXgtLN8PzJpJQwIZrgbB1Tkbu--sSXwqRMb-mFXcmMUpNE2NEE-TL_zi8-ptxEmSAdNZYxXl4iSMHshikjLhWxXlnSXwG-ppOJRr-QaEF-UM-HMtvyUZDpQ';
//        $mutations = new \Moota\Moota\Domain\Mutation();
//
//        $params = [
//            'type'          => '',
//            'bank'          => '',
//            'amount'        => '',
//            'description'   => '',
//            'note'          => '',
//            'date'          => '',
//            'start_date'    => '',
//            'end_date'      => '',
//            'tag'           => '',
//            'page'          => 1,
//            'per_page'      => 20
//        ];
//        $response = $mutations->getMutations($params);
//        print_r($response);exit;