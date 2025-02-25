<?php

namespace Newerton\Yii2Boleto\Cnab\Remessa;

use Newerton\Yii2Boleto\Contracts\Boleto\Boleto as BoletoContract;
use Newerton\Yii2Boleto\Contracts\Pessoa as PessoaContract;
use Newerton\Yii2Boleto\Support\Collection;
use Newerton\Yii2Boleto\Util;

/**
 * Class AbstractRemessa
 * @package Newerton\Yii2Boleto\Cnab\Remessa
 */
abstract class AbstractRemessa
{

    const HEADER = 'header';
    const HEADER_LOTE = 'header_lote';
    const DETALHE = 'detalhe';
    const TRAILER_LOTE = 'trailer_lote';
    const TRAILER = 'trailer';

    protected $tamanho_linha = false;

    /**
     * Campos que são necessários para a remessa
     *
     * @var array
     */
    private $camposObrigatorios = [
        'carteira',
        'agencia',
        'conta',
        'beneficiario',
    ];

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco;

    /**
     * Contagem dos registros Detalhes
     *
     * @var int
     */
    protected $iRegistros = 0;

    /**
     * Array contendo o cnab.
     *
     * @var array
     */
    protected $aRegistros = [
        self::HEADER => [],
        self::DETALHE => [],
        self::TRAILER => [],
    ];

    /**
     * Variavel com ponteiro para linha que esta sendo editada.
     *
     * @var
     */
    protected $atual;

    /**
     * Caracter de fim de linha
     *
     * @var string
     */
    protected $fimLinha = "\n";

    /**
     * Caracter de fim de arquivo
     *
     * @var null
     */
    protected $fimArquivo = null;

    /**
     * ID do arquivo remessa, sequencial.
     *
     * @var
     */
    protected $idremessa;

    /**
     * Responsável pela entrega dos boletos
     *
     * @var int
     */
    protected $responsavelEntregaBoletos;

    /**
     * Codigo de Transmissao do arquivo
     *
     * @var string
     */
    protected $codigoTransmissao;

    /**
     * Agência
     *
     * @var int
     */
    protected $agencia;

    /**
     * DV da Agência
     *
     * @var int
     */
    protected $agenciaDv;

    /**
     * Qtde de dias para protesto
     *
     * @var int
     */
    protected $diasProtesto;

    /**
     * Qtde de dias para protesto
     *
     * @var int
     */
    protected $diasBaixa;

    /**
     * Conta
     *
     * @var int
     */
    protected $conta;

    /**
     * Dígito da conta
     *
     * @var int
     */
    protected $contaDv;

    /**
     * Carteira de cobrança.
     *
     * @var
     */
    protected $carteira;

    /**
     * Define as carteiras disponíveis para cada banco
     *
     * @var array
     */
    protected $carteiras = [];

    /**
     * Entidade beneficiario (quem esta gerando a remessa)
     *
     * @var PessoaContract
     */
    protected $beneficiario;

    /**
     * Construtor
     *
     * @param array $params Parâmetros iniciais para construção do objeto
     */
    public function __construct($params = [])
    {
        $this->setAgenciaDv('0');
        Util::fillClass($this, $params);
    }

    /**
     * Seta os campos obrigatórios
     *
     * @return $this
     */
    protected function setCamposObrigatorios()
    {
        $args = func_get_args();
        $this->camposObrigatorios = [];
        foreach ($args as $arg) {
            $this->addCampoObrigatorio($arg);
        }

        return $this;
    }

    /**
     * Adiciona os campos obrigatórios
     *
     * @return $this
     */
    protected function addCampoObrigatorio()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            !is_array($arg) || call_user_func_array([$this, __FUNCTION__], $arg);
            !is_string($arg) || array_push($this->camposObrigatorios, $arg);
        }

        return $this;
    }

    /**
     * Retorna o código do banco
     *
     * @return string
     */
    public function getCodigoBanco()
    {
        return $this->codigoBanco;
    }

    /**
     * @return mixed
     */
    public function getIdremessa()
    {
        return $this->idremessa;
    }

    /**
     * @param mixed $idremessa
     *
     * @return AbstractRemessa
     */
    public function setIdremessa($idremessa)
    {
        $this->idremessa = $idremessa;

        return $this;
    }

    /**
     * @return PessoaContract
     */
    public function getBeneficiario()
    {
        return $this->beneficiario;
    }

    /**
     * @param $beneficiario
     *
     * @return AbstractRemessa
     * @throws \Exception
     */
    public function setBeneficiario($beneficiario)
    {
        Util::addPessoa($this->beneficiario, $beneficiario);

        return $this;
    }

    /**
     * Define a agência
     *
     * @param $agencia
     * @return $this
     */
    public function setAgencia($agencia)
    {
        $this->agencia = (string)$agencia;

        return $this;
    }

    /**
     * Retorna a agência
     *
     * @return int
     */
    public function getAgencia()
    {
        return $this->agencia;
    }


    /**
     * Define o DV da agência
     *
     * @param $agencia
     * @return $this
     */
    public function setAgenciaDv($agenciaDv)
    {
        $this->agenciaDv = (string)$agenciaDv;

        return $this;
    }


    /**
     * Retorna o DV da agência
     *
     * @return int
     */
    public function getAgenciaDv()
    {
        return $this->agenciaDv;
    }


    /**
     * Define o número da conta
     *
     * @param $conta
     * @return $this
     */
    public function setConta($conta)
    {
        $this->conta = (string)$conta;

        return $this;
    }

    /**
     * Retorna o número da conta
     *
     * @return int
     */
    public function getConta()
    {
        return $this->conta;
    }

    /**
     * Define o dígito verificador da conta
     *
     * @param $contaDv
     * @return $this
     */
    public function setContaDv($contaDv)
    {
        $this->contaDv = substr($contaDv, -1);

        return $this;
    }

    /**
     * Retorna o dígito verificador da conta
     *
     * @return int
     */
    public function getContaDv()
    {
        return $this->contaDv;
    }

    /**
     * Define o código da carteira (Com ou sem registro)
     *
     * @param $carteira
     * @return $this
     * @throws \Exception
     */
    public function setCarteira($carteira)
    {
        if (!in_array($carteira, $this->getCarteiras())) {
            throw new \Exception("Carteira não disponível!");
        }
        $this->carteira = $carteira;

        return $this;
    }

    /**
     * Retorna o código da carteira (Com ou sem registro)
     *
     * @return string
     */
    public function getCarteira()
    {
        return $this->carteira;
    }

    /**
     * Retorna o código da carteira (Com ou sem registro)
     *
     * @return string
     */
    public function getCarteiraNumero()
    {
        return $this->carteira;
    }

    /**
     * Retorna as carteiras disponíveis para este banco
     *
     * @return array
     */
    public function getCarteiras()
    {
        return $this->carteiras;
    }

    public function errosValidacao() {

        $ret = [];

        foreach ($this->camposObrigatorios as $campo) {
            if (call_user_func([$this, 'get' . ucwords($campo)]) == '') {
                $ret[] = ucwords($campo);
            }
        }

        return $ret;

    }

    /**
     * Método que valida se o banco tem todos os campos obrigadotorios preenchidos
     *
     * @return boolean
     */
    public function isValid()
    {
        foreach ($this->camposObrigatorios as $campo) {
            if (call_user_func([$this, 'get' . ucwords($campo)]) == '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Função para gerar o cabeçalho do arquivo.
     *
     * @return mixed
     */
    abstract protected function header();

    /**
     * Função para adicionar detalhe ao arquivo.
     *
     * @param BoletoContract $detalhe
     * @return mixed
     */
    abstract public function addBoleto(BoletoContract $detalhe);

    /**
     * Função que gera o trailer (footer) do arquivo.
     *
     * @return mixed
     */
    abstract protected function trailer();

    /**
     * Função que mostra a quantidade de linhas do arquivo.
     *
     * @return int
     */
    protected function getCount()
    {
        return count($this->aRegistros[self::DETALHE]) + 2;
    }

    /**
     * Função para adicionar multiplos boletos.
     *
     * @param array $boletos
     * @return $this
     */
    public function addBoletos(array $boletos)
    {
        foreach ($boletos as $boleto) {
            $this->addBoleto($boleto);
        }

        return $this;
    }

    /**
     * Função para add valor a linha nas posições informadas.
     *
     * @param $i
     * @param $f
     * @param $value
     * @return array
     */
    protected function add($i, $f, $value)
    {
        return Util::adiciona($this->atual, $i, $f, $value);
    }

    /**
     * Retorna o header do arquivo.
     *
     * @return mixed
     */
    protected function getHeader()
    {
        return $this->aRegistros[self::HEADER];
    }

    /**
     * Retorna os detalhes do arquivo
     *
     * @return Collection
     */
    protected function getDetalhes()
    {
        return new Collection($this->aRegistros[self::DETALHE]);
    }

    /**
     * Retorna o trailer do arquivo.
     *
     * @return mixed
     */
    protected function getTrailer()
    {
        return $this->aRegistros[self::TRAILER];
    }

    /**
     * Valida se a linha esta correta.
     *
     * @param array $a
     * @return string
     * @throws \Exception
     */
    protected function valida(array $a)
    {
        if ($this->tamanho_linha === false) {
            throw new \Exception('Classe remessa deve informar o tamanho da linha');
        }

        $a = array_filter($a, 'strlen');
        if (count($a) != $this->tamanho_linha) {
            throw new \Exception(sprintf('$a não possui %s posições, possui: %s', $this->tamanho_linha, count($a)));
        }

        return implode('', $a);
    }

    /**
     * Gera o arquivo, retorna a string.
     *
     * @throws \Exception
     */
    public function gerar()
    {
        throw new \Exception('Método não implementado');
    }

    /**
     * Salva o arquivo no path informado
     *
     * @param $path
     * @return mixed
     * @throws \Exception
     */
    public function save($path)
    {
        $folder = dirname($path);
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        if (!is_writable(dirname($path))) {
            throw new \Exception('Path ' . $folder . ' não possui permissao de escrita');
        }

        $string = $this->gerar();
        file_put_contents($path, $string);

        return $path;
    }

    /**
     * Realiza o download da string retornada do metodo gerar
     *
     * @param null $filename
     */
    public function download($filename = null)
    {
        if ($filename === null) {
            $filename = 'remessa.txt';
        }
        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $this->gerar();
    }

    /**
     * @return int
     */
    public function getDiasProtesto(): int
    {
        return $this->diasProtesto;
    }

    /**
     * @param int $diasProtesto
     */
    public function setDiasProtesto(int $diasProtesto): void
    {
        $this->diasProtesto = $diasProtesto;
    }

    /**
     * @return int
     */
    public function getDiasBaixa()
    {
        return $this->diasBaixa;
    }

    /**
     * @param int $diasBaixa
     */
    public function setDiasBaixa(int $diasBaixa)
    {
        $this->diasBaixa = $diasBaixa;
    }

    /**
     * @return int
     */
    public function getResponsavelEntregaBoletos()
    {
        return $this->responsavelEntregaBoletos;
    }

    /**
     * @param int $responsavelEntregaBoletos
     */
    public function setResponsavelEntregaBoletos($responsavelEntregaBoletos)
    {
        $this->responsavelEntregaBoletos = $responsavelEntregaBoletos;
    }

    /**
     * @return string
     */
    public function getCodigoTransmissao()
    {
        return $this->codigoTransmissao;
    }

    /**
     * @param string $codigoTransmissao
     */
    public function setCodigoTransmissao($codigoTransmissao)
    {
        $this->codigoTransmissao = $codigoTransmissao;
    }





}