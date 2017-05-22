<?php namespace Princealikhan\Mautic;

use Princealikhan\Mautic\Factories\MauticFactory;
use GrahamCampbell\Manager\AbstractManager;
use Illuminate\Contracts\Config\Repository;
use Princealikhan\Mautic\Models\MauticConsumer;

/**
 * Class Mautic
 * @package Princealikhan\Mautic
 */
class Mautic extends AbstractManager
{

    /**
     * The factory instance.
     *
     * @var MauticFactory
     */
    protected $factory;

    /**
     * Create a new Mautic manager instance.
     *
     * @param $config
     * @param $factory
     *
     */
    public function __construct(Repository $config, MauticFactory $factory)
    {
        parent::__construct($config);

        $this->factory = $factory;
    }

    /**
     * Create the connection instance.
     *
     * @param array $config
     *
     * @return mixed
     */
    protected function createConnection(array $config)
    {
        return $this->factory->make($config);
    }

    /**
     * Get the configuration name.
     *
     * @return string
     */
    protected function getConfigName()
    {
        return 'mautic';
    }

    /**
     * Get the factory instance.
     *
     * @return MauticFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param null $method
     * @param null $endpoints
     * @param null $body
     * @return mixed
     */
    public function request($method = null, $endpoints = null, $body = null)
    {
        $connection = $this->factory->getDefaultConnection();
        if (isset($connection['version']) && 'BasicAuth' == $connection['version']) {
            return $this->factory->callMautic($method, $endpoints, $body);
        }

        $consumer = MauticConsumer::whereNotNull('id')
            ->orderBy('created_at', 'desc')
            ->first();

        $expirationStatus = $this->factory->checkExpirationTime($consumer->expires);

        if ($expirationStatus == true) {
            $newToken = $this->factory->refreshToken($consumer->refresh_token);
            return $this->factory->callMautic($method, $endpoints, $body, $newToken->access_token);
        } else {
            return $this->factory->callMautic($method, $endpoints, $body, $consumer->access_token);
        }
    }

    /**
     * @param array $data
     * @return null|object
     */
    public function contactCreate(array $data)
    {
        $companies = $userCompanies = [];
        if (isset($data['companies'])) {
            $companies = $data['companies'];
            unset($data['companies']);
        }

        $response = Mautic::request('POST', 'contacts/new', $data);
        if (!isset($response['contact'])) {
            return null;
        }

        $user = (object)$response['contact'];

        foreach ($companies as $company) {
            $response = Mautic::request('POST', 'companies/new', $company);
            if (isset($response['company'])) {
                $userCompany = (object)$response['company'];
                $userCompanies[] = $userCompany;
                Mautic::request('POST', 'companies/' . $userCompany->id . '/contact/add/' . $user->id);
            }
        }

        return $user;
    }
}
