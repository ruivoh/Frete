<?php

namespace EscapeWork\Frete\Correios;

use EscapeWork\Frete\FreteException;
use EscapeWork\Frete\Collection;
use GuzzleHttp\Client;
use Exception, InvalidArgumentException;
use SoapClient;

class Rastreamento extends BaseCorreios
{

    /**
     * Guzzle client
     * @var GuzzleHttp\Client
     */
    protected $client;

    /**
     * Result
     * @var EscapeWork\Frete\Correios\RastreamentoResult
     */
    protected $result;

    /**
     * Data
     * @var array
     */
    protected $data = array(
        'Usuario'   => '',
        'Senha'     => '',
        'Tipo'      => 'L',
        'Resultado' => 'U',
        'Objetos'   => [],
    );

    public function __construct(RastreamentoResult $result = null)
    {
        if (! $this->result = $result) {
            $this->result = new RastreamentoResult;
        }
    }

    public function setUsuario($usuario)
    {
        $this->data['Usuario'] = $usuario;
        return $this;
    }

    public function setSenha($senha)
    {
        $this->data['Senha'] = $senha;
        return $this;
    }

    public function setTipo($tipo)
    {
        if (! in_array($tipo, array('L', 'F'))) {
            throw new InvalidArgumentException('Apenas os valores L ou F são suportados para o tipo');
        }

        $this->data['Tipo'] = $tipo;
        return $this;
    }

    public function setResultado($resultado)
    {
        if (! in_array($resultado, array('T', 'U'))) {
            throw new InvalidArgumentException('Apenas os valores T ou U são suportados para o tipo');
        }

        $this->data['Resultado'] = $resultado;
        return $this;
    }

    public function setObjetos($objetos)
    {
        $this->data['Objetos'] = (array) $objetos;
        return $this;
    }

    public function track()
    {
        ini_set('default_socket_timeout', 1);

        try {
            $client   = new SoapClient(Data::URL_RASTREAMENTO);
            $response = $client->buscaEventos($this->getData());

            return $this->result($response->return);
        } catch (Exception $e) {
            throw new FreteException('Houve um erro ao buscar os dados. Verifique se todos os dados estão corretos', 1);
        }
    }

    protected function result($data)
    {
        if (! isset($data->error)) {
            if (isset($data->objeto->numero)) {
                $this->result->fill($data->objeto);

                return $this->result;
            } else {
                return $this->makeCollection($data);
            }
        } else {
            throw new FreteException($data->error, 0);
        }
    }

    protected function getData()
    {
        return array(
            'usuario'   => $this->data['Usuario'],
            'senha'     => $this->data['Senha'],
            'tipo'      => $this->data['Tipo'],
            'resultado' => $this->data['Resultado'],
            'lingua'    => '101',
            'objetos'   => implode('', $this->data['Objetos']),
        );
    }

    protected function makeCollection($data)
    {
        $objects = new Collection;

        foreach ($data['objeto'] as $objeto) {
            $result = new RastreamentoResult();
            $result->fill($objeto);

            $objects[] = $result;
        }

        return $objects;
    }
}
