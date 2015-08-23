<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Email;

class ControllerTest extends TestCase
{
    protected $key;

    protected $serverParams;

    use DatabaseTransactions;


    public function setUp()
    {
        parent::setUp();
        $this->key = 'key:' . env('APP_KEY');
        $this->serverParams = [
            'HTTP_ACCEPT'      => 'application/vnd.app.v1+json',
            'HTTP_API_TOKEN' => $this->key
        ];
    }

    function testApitWithoutCredentials()
    {

        $this->call('GET', '/api/emails');
        $this->assertResponseStatus(400);

    }

    function testApitWithBadCredentials()
    {
        $serverParams = array_replace(
            $this->serverParams,
            ['HTTP_API_TOKEN' => 'key:1122221eewew']
        );

        $this->call('GET', '/api/emails', [], [], [], $serverParams);
        $this->assertResponseStatus(401);
    }

    function testGetEmails()
    {
        $response = $this->call('GET', '/api/emails', [], [], [], $this->serverParams);
        $json = json_decode($response->getContent());

        $this->assertResponseOk();

        /*
         * check and test json response
         */
        $this->assertTrue(isset($json->meta));
        $this->assertTrue(isset($json->data));

    }

    function testGetEmail()
    {
        $email = $this->generateEmail();
        $response = $this->call('GET', "/api/emails/{$email->id}", [], [], [], $this->serverParams);
        $json = json_decode($response->getContent());

        $this->assertResponseOk();

        /*
         * check and test json response
         */
        $this->assertTrue(isset($json->data));
        $this->assertTrue(isset($json->data->id));
        $this->assertEquals($json->data->id, $email->id);
    }

    function testGetEmailNotFound()
    {
        $this->call('GET', "/api/emails/0", [], [], [], $this->serverParams);
        $this->assertResponseStatus(422);
    }

    function testDeleteEmail()
    {
        $email = $this->generateEmail();
        $this->call('DELETE', "/api/emails/{$email->id}", [], [], [], $this->serverParams);
        $this->assertResponseStatus(204);

        $this->notSeeInDatabase($email->getTable(), ['id' => $email->id]);
    }

    function testDeleteEmailNotFound()
    {
        $this->call('DELETE', "/api/emails/0", [], [], [], $this->serverParams);
        $this->assertResponseStatus(422);
    }

    protected function generateEmail()
    {
        $data = [
            'to' => 'r.lacerda83@gmail.com',
            'send_type' => 'queue',
            'subject' => 'Test',
            'html' => '<html></html>'
        ];

        return Email::create($data);
    }
}