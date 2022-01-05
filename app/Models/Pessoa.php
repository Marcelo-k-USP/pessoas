<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Uspdev\Replicado\Pessoa as PessoaReplicado;
use Uspdev\Replicado\Posgraduacao;

class Pessoa extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function getDataNascimentoAttribute($value)
    {
        return implode('/', array_reverse(explode('-', $value)));
    }

    public function setDataNascimentoAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['data_nascimento'] = implode('-', array_reverse(explode('/', $value)));
        }
    }

    public function getValidadeVistoAttribute($value)
    {
        if (!empty($value)) {
            return implode('/', array_reverse(explode('-', $value)));
        }
    }

    public function setValidadeVistoAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['validade_visto'] = implode('-', array_reverse(explode('/', $value)));
        }
    }

    public function getCpfAttribute($value)
    {
        if (!empty($value)) {
            return substr($value, 0, 3) . '.' . substr($value, 3, 3) . '.' . substr($value, 6, 3) . '-' . substr($value, 9, 2);
        }
    }

    public function setCpfAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['cpf'] = preg_replace("/[^0-9]/", "", $value);
        }
    }

    public function replicado()
    {
        // Formata endereço
        if ($endereco = PessoaReplicado::obterEndereco($this->codpes)) {
            $endereco = "
                {$endereco['nomtiplgr']} {$endereco['epflgr']} ,
                {$endereco['numlgr']} {$endereco['cpllgr']} -
                {$endereco['nombro']} - {$endereco['cidloc']}  -
                {$endereco['sglest']} - CEP: {$endereco['codendptl']}
            ";
        } else {
            $endereco = 'Não encontrado';
        }

        $dump = PessoaReplicado::dump($this->codpes);

        if ($dump['nompes'] != $dump['nompesttd']) {
            $nome = $dump['nompesttd'] . '(' . $dump['nompes'] . ')';
        } else {
            $nome = $dump['nompes'];
        }

        if ($dump['sexpes'] == 'M') {
            $genero = 'Masculino';
        } elseif ($dump['sexpes'] == "F") {
            $genero = 'Feminino';
        } else {
            $genero = 'Não informado';
        }

        $cpf = $dump['numcpf'];
        $len = strlen($cpf);
        while($len < 11){
            $cpf = "0".$cpf;
            $len++;
        }
        $cpf = $this->getCpfAttribute($cpf);
        $documentos = "
            CPF: {$cpf},
            {$dump['tipdocidf']}: {$dump['numdocfmt']} {$dump['sglorgexdidf']}/{$dump['sglest']}
            ";

        return [
            'nome' => $nome,
            'documentos' => $documentos,
            'nasc' => \Carbon\Carbon::parse($dump['dtanas'])->format('d/m/Y'),
            'genero' => $genero,
            'telefones' => PessoaReplicado::telefones($this->codpes),
            'emails' => PessoaReplicado::emails($this->codpes),
            'vinculos' => SELF::listarVinculosAtivos($this->codpes),
            'endereco' => $endereco,
            'ramal' => PessoaReplicado::obterRamalUsp($this->codpes),
        ];
    }

    /**
     * Deve ir para o replicado\Pessoa
     * Retorna dados básicos de vínculos ativos de determinada pessoa (codpes) FROM
     *
     * @param Int $codpes Número USP da pessoa
     * @return Collection todos os vinculos ativos no formato array de arrays
     * @author Masaki K Neto, em 4/2021
     */
    public static function listarVinculosAtivos(int $codpes)
    {
        $query = "SELECT * FROM LOCALIZAPESSOA
                  WHERE codpes = convert(int,:codpes)";
        $param['codpes'] = $codpes;

        return \Uspdev\Replicado\DB::fetchAll($query, $param);
    }

    /**
     * Formata o vinculo da pessoa para ser apresentado
     *
     * Para cada tipo de vinculo mostra os dados necessários
     *
     * @param Array $vinculo conforme consulta do Replicado::listarVinculosAtivos
     * @return String
     * @author Masaki K Neto, 4/2021
     */
    public function vinculoFormatado($vinculo)
    {
        switch ($vinculo['tipvin']) {
            case 'ALUNOPOS':
                if ($pg = Posgraduacao::obterVinculoAtivo($this->codpes)) {
                    return $vinculo['tipvinext']
                    . ', programa: ' . $pg['nomcur']
                    . ', área: ' . $pg['nomare']
                    . ', nível: ' . $pg['nivpgm']
                    . ', orientador: <a href="pessoas/' . $pg['codpesori'] . '">' . $pg['nompesori'] . '</a>'
                    . ', ingresso: ' . date('d/m/Y', strtotime($vinculo['dtainivin']));
                }
                break;
            //case 'SERVIDOR':
            //    break;
            default:
                $ret = '';

                if (!empty($vinculo['tipvinext'])) {
                    $ret = $ret . $vinculo['tipvinext'];
                }

                if (!empty($vinculo['nomfnc'])) {
                    $ret = $ret . " - " . $vinculo['nomfnc'];
                }

                if (!empty($vinculo['nomset'])) {
                    $ret = $ret . " - " . $vinculo['nomset'];
                }

                if (!empty($vinculo['sglclgund'])) {
                    $ret = $ret . " - " . $vinculo['sglclgund'];
                }

                return $ret;
        }
    }
}
