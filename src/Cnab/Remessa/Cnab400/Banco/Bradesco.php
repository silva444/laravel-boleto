<?php

namespace Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab400\Banco;

use Eduardokum\LaravelBoleto\Util;
use Eduardokum\LaravelBoleto\CalculoDV;
use Eduardokum\LaravelBoleto\Exception\ValidationException;
use Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab400\AbstractRemessa;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;
use Eduardokum\LaravelBoleto\Contracts\Cnab\Remessa as RemessaContract;

class Bradesco extends AbstractRemessa implements RemessaContract
{
    const ESPECIE_DUPLICATA = '01';
    const ESPECIE_NOTA_PROMISSORIA = '02';
    const ESPECIE_NOTA_SEGURO = '03';
    const ESPECIE_COBRANCA_SERIADA = '04';
    const ESPECIE_RECIBO = '05';
    const ESPECIE_LETRAS_CAMBIO = '10';
    const ESPECIE_NOTA_DEBITO = '11';
    const ESPECIE_DUPLICATA_SERVICO = '12';
    const ESPECIE_OUTROS = '99';
    const OCORRENCIA_REMESSA = '01';
    const OCORRENCIA_PEDIDO_BAIXA = '02';
    const OCORRENCIA_CONCESSAO_ABATIMENTO = '04';
    const OCORRENCIA_CANC_ABATIMENTO_CONCEDIDO = '05';
    const OCORRENCIA_ALT_VENCIMENTO = '06';
    const OCORRENCIA_ALT_CONTROLE_PARTICIPANTE = '07';
    const OCORRENCIA_ALT_SEU_NUMERO = '08';
    const OCORRENCIA_PEDIDO_PROTESTO = '09';
    const OCORRENCIA_SUSTAR_PROTESTO_BAIXAR_TITULO = '18';
    const OCORRENCIA_SUSTAR_PROTESTO_MANTER_TITULO = '19';
    const OCORRENCIA_TRANS_CESSAO_CREDITO_ID10 = '22';
    const OCORRENCIA_TRANS_CARTEIRAS = '23';
    const OCORRENCIA_DEVOLUCAO_TRANS_CARTEIRAS = '24';
    const OCORRENCIA_ALT_OUTROS_DADOS = '31';
    const OCORRENCIA_DESAGENDAMENTO_DEBITO_AUT = '35';
    const OCORRENCIA_ACERTO_RATEIO_CREDITO = '68';
    const OCORRENCIA_CANC_RATEIO_CREDITO = '69';
    const INSTRUCAO_SEM = '00';
    const INSTRUCAO_PROTESTAR_FAMILIAR_XX = '05';
    const INSTRUCAO_PROTESTAR_XX = '06';
    const INSTRUCAO_NAO_COBRAR_JUROS = '08';
    const INSTRUCAO_NAO_RECEBER_APOS_VENC = '09';
    const INSTRUCAO_MULTA_10_APOS_VENC_4 = '10';
    const INSTRUCAO_NAO_RECEBER_APOS_VENC_8 = '11';
    const INSTRUCAO_COBRAR_ENCAR_APOS_5 = '12';
    const INSTRUCAO_COBRAR_ENCAR_APOS_10 = '13';
    const INSTRUCAO_COBRAR_ENCAR_APOS_15 = '14';
    const INSTRUCAO_CENCEDER_DESC_APOS_VENC = '15';
    const INSTRUCAO_DEVOLVER_XX = '18';

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->addCampoObrigatorio('idremessa');
    }

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = BoletoContract::COD_BANCO_BRADESCO;

    /**
     * Define as carteiras disponíveis para cada banco
     *
     * @var array
     */
    protected $carteiras = ['02', '04', '09', '28'];

    /**
     * Caracter de fim de linha
     *
     * @var string
     */
    protected $fimLinha = "\r\n";

    /**
     * Caracter de fim de arquivo
     *
     * @var null
     */
    protected $fimArquivo = "\r\n";

    /**
     * Codigo do cliente junto ao banco.
     *
     * @var string
     */
    protected $codigoCliente;

    /**
     * Retorna o codigo do cliente.
     *
     * @return mixed
     * @throws ValidationException
     */
    public function getCodigoCliente()
    {
        if (empty($this->codigoCliente)) {
            $this->codigoCliente = Util::formatCnab('9', $this->getCarteiraNumero(), 4) .
                Util::formatCnab('9', $this->getAgencia(), 5) .
                Util::formatCnab('9', $this->getConta(), 7) .
                Util::formatCnab('9', ! is_null($this->getContaDv()) ? $this->getContaDv() : CalculoDV::bradescoContaCorrente($this->getConta()), 1);
        }

        return $this->codigoCliente;
    }

    /**
     * Seta o codigo do cliente.
     *
     * @param mixed $codigoCliente
     *
     * @return Bradesco
     */
    public function setCodigoCliente($codigoCliente)
    {
        $this->codigoCliente = $codigoCliente;

        return $this;
    }

    /**
     * @return Bradesco
     * @throws ValidationException
     */
    protected function header()
    {
        $this->iniciaHeader();

        $this->add(1, 1, '0');
        $this->add(2, 2, '1');
        $this->add(3, 9, 'REMESSA');
        $this->add(10, 11, '01');
        $this->add(12, 26, Util::formatCnab('X', 'COBRANCA', 15));
        $this->add(27, 46, Util::formatCnab('9', $this->getCodigoCliente(), 20));
        $this->add(47, 76, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(77, 79, $this->getCodigoBanco());
        $this->add(80, 94, Util::formatCnab('X', 'Bradesco', 15));
        $this->add(95, 100, $this->getDataRemessa('dmy'));
        $this->add(101, 108, '');
        $this->add(109, 110, 'MX');
        $this->add(111, 117, Util::formatCnab('9', $this->getIdremessa(), 7));
        $this->add(118, 394, '');
        $this->add(395, 400, Util::formatCnab('9', 1, 6));

        return $this;
    }

    /**
     * @param \Eduardokum\LaravelBoleto\Boleto\Banco\Bradesco $boleto
     *
     * @return Bradesco
     * @throws ValidationException
     */
    public function addBoleto(BoletoContract $boleto)
    {
        $this->boletos[] = $boleto;
        if ($chaveNfe = $boleto->getChaveNfe()) {
            $this->iniciaDetalheExtendido();
        } else {
            $this->iniciaDetalhe();
        }

        $this->add(1, 1, '1');
        $this->add(1, 1, '1');
        $this->add(2, 2, '0');
        $this->add(3, 3, '0');
        $this->add(4, 4, '0');
        $this->add(5, 5, '0');
        $this->add(6, 6, '0');
        $this->add(7, 7, '');
        $this->add(8, 8, '0');
        $this->add(9, 9, '0');
        $this->add(10, 10, '0');
        $this->add(11,11, '0');
        $this->add(12,12, '0');
        $this->add(13,13, '0');
        $this->add(14,14, '0');
        $this->add(15,15, '0');
        $this->add(16,16, '0');
        $this->add(17,17, '0');
        $this->add(18,18, '0');
        $this->add(19,19, '0');
        // $this->add(20, 20, '');
        $this->add(21, 21, '0');
        $this->add(22, 24, Util::formatCnab('9', $this->getCarteira(), 3));
        $this->add(25, 29, Util::formatCnab('9', $this->getAgencia(), 5));
        $this->add(30, 36, Util::formatCnab('9', $this->getConta(), 7));
        $this->add(37, 37, Util::formatCnab('9', $this->getContaDv(), 1));
        $this->add(38, 62, Util::formatCnab('X', $boleto->getNumeroControle(), 25)); // numero de controle
        $this->add(63, 65, '000');
        $this->add(66, 66, $boleto->getMulta() > 0 ? '2' : '0');
        $this->add(67, 70, Util::formatCnab('9', $boleto->getMulta() > 0 ? $boleto->getMulta() : '0', 4, 2));
        $this->add(71, 82, Util::formatCnab('9', $boleto->getNossoNumero(), 12));
        $this->add(83, 92, Util::formatCnab('9', 0, 10, 2));
        $this->add(93, 93, '2'); // 1 = Banco emite e Processa o registro. 2 = Cliente emite e o Banco somente processa o registro
        $this->add(94, 94, 'N'); // N= Não registra na cobrança. Diferente de N registra e emite Boleto.
        $this->add(95, 104, '');
        $this->add(105, 105, '');
        $this->add(106, 106, '2'); // 1 = emite aviso, e assume o endereço do Pagador constante do Arquivo-Remessa; 2 = não emite aviso;
        $this->add(107, 108, '');
        $this->add(109, 110, self::OCORRENCIA_REMESSA); // REGISTRO
        if ($boleto->getStatus() == $boleto::STATUS_BAIXA) {
            $this->add(109, 110, self::OCORRENCIA_PEDIDO_BAIXA); // BAIXA
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO) {
            $this->add(109, 110, self::OCORRENCIA_ALT_VENCIMENTO); // ALTERAR VENCIMENTO
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO_DATA) {
            $this->add(109, 110, self::OCORRENCIA_ALT_VENCIMENTO);
        }
        if ($boleto->getStatus() == $boleto::STATUS_CUSTOM) {
            $this->add(109, 110, sprintf('%2.02s', $boleto->getComando()));
        }
        $this->add(111, 120, Util::formatCnab('X', $boleto->getNumeroDocumento(), 10));
        $this->add(121, 126, $boleto->getDataVencimento()->format('dmy'));
        $this->add(127, 139, Util::formatCnab('9', $boleto->getValor(), 13, 2));
        $this->add(140, 142, '000');
        $this->add(143, 147, '00000');
        $this->add(148, 149, $boleto->getEspecieDocCodigo());
        $this->add(150, 150, 'N');
        $this->add(151, 156, $boleto->getDataDocumento()->format('dmy'));
        $this->add(157, 158, self::INSTRUCAO_SEM);
        $this->add(159, 160, self::INSTRUCAO_SEM);
        if ($boleto->getDiasProtesto() > 0) {
            $this->add(157, 158, self::INSTRUCAO_PROTESTAR_XX);
            $this->add(159, 160, Util::formatCnab('9', $boleto->getDiasProtesto(), 2));
        } elseif ($boleto->getDiasBaixaAutomatica() > 0) {
            $this->add(157, 158, self::INSTRUCAO_DEVOLVER_XX);
            $this->add(159, 160, Util::formatCnab('9', $boleto->getDiasBaixaAutomatica(), 2));
        }
        $this->add(161, 173, Util::formatCnab('9', $boleto->getMoraDia(), 13, 2));
        $this->add(174, 179, $boleto->getDesconto() > 0 ? $boleto->getDataDesconto()->format('dmy') : '000000');
        $this->add(180, 192, Util::formatCnab('9', $boleto->getDesconto(), 13, 2));
        $this->add(193, 205, Util::formatCnab('9', 0, 13, 2));
        $this->add(206, 218, Util::formatCnab('9', 0, 13, 2));
        $this->add(219, 220, strlen(Util::onlyNumbers($boleto->getPagador()->getDocumento())) == 14 ? '02' : '01');
        $this->add(221, 234, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getDocumento()), 14));
        $this->add(235, 274, Util::formatCnab('X', $boleto->getPagador()->getNome(), 40));
        $this->add(275, 314, Util::formatCnab('X', $boleto->getPagador()->getEndereco(), 40));
        $this->add(315, 326, Util::formatCnab('X', $boleto->getPagador()->getBairro(), 12));
        $this->add(327, 334, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getCep()), 8));
        $this->add(335, 394, Util::formatCnab('X', $boleto->getSacadorAvalista() ? $boleto->getSacadorAvalista()->getNome() : '', 60));
        $this->add(395, 400, Util::formatCnab('9', $this->iRegistros + 1, 6));
        $this->add(336, 336, 'D');
        $this->add(337, 337, 'E');
        $this->add(338, 338, 'P');
        $this->add(339, 339, 'O');
        $this->add(340, 340, 'S');
        $this->add(341, 341, 'I');
        $this->add(342, 342, 'T');
        $this->add(343, 343, 'O');
        $this->add(344, 344, 'S');
        $this->add(345, 345, '');
        $this->add(346, 346, 'N');
        $this->add(347, 347, 'A'); 
        $this->add(348, 348, 'O'); 
        $this->add(349, 349, ''); 
        $this->add(350, 350, 'S'); 
        $this->add(351, 351, 'E'); 
        $this->add(352, 352, 'R'); 
        $this->add(353, 353, 'A'); 
        $this->add(354, 354, 'O'); 
        $this->add(355, 355, ''); 
        $this->add(356, 356, 'A'); 
        $this->add(357, 357, 'C'); 
        $this->add(358, 358, 'E'); 
        $this->add(359, 359, 'I'); 
        $this->add(360, 360, 'T'); 
        $this->add(361, 361, 'O'); 
        $this->add(362, 362, 'S'); 
        $this->add(363, 363, ''); 
        $this->add(364, 364, 'C'); 
        $this->add(365, 365, 'O'); 
        $this->add(366, 366, 'M'); 
        $this->add(367, 367, 'O');
        $this->add(368, 368, '');
        $this->add(369, 369, 'Q');
        $this->add(370, 370, 'U');
        $this->add(371, 371, 'I');
        $this->add(372, 372, 'T');
        $this->add(373, 373, 'A');
        $this->add(374, 374, 'C');
        $this->add(375, 375, 'A');
        $this->add(376, 376, 'O');
        if ($chaveNfe) {
            $this->add(401, 444, Util::formatCnab('9', $chaveNfe, 44));
        }

        return $this;
    }

    /**
     * @return Bradesco
     * @throws ValidationException
     */
    protected function trailer()
    {
        $this->iniciaTrailer();

        $this->add(1, 1, '9');
        $this->add(2, 394, '');
        $this->add(395, 400, Util::formatCnab('9', $this->getCount(), 6));

        return $this;
    }
}
