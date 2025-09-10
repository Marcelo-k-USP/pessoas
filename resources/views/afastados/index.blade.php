@extends('layouts.app')

@section('content')
    <h2>Lista de Servidores Afastados</h2>
    <table class="table table-striped table-sm table-afastados">
        <thead>
            <th>N° USP</th>
            <th>Nome</th>
            <th>Setor</th>
            <th>Motivo do Afastamento</th>
            <th>Data de Início</th>
            <th>Data de Término</th>
            <th>E-mail</th>
            <th>Telefones</th>
        </thead>
        <tbody>
            @foreach ($afastados as $afastado)
            <tr>
               <td>{{$afastado['codpes']}}</td> 
               <td><a href="{{route('pessoas.show', $afastado['codpes'])}}">{{$afastado['nompes']}}</a></td> 
               <td>{{$afastado['nomabvset']}}</td> 
               <td>{{$afastado['sitoco']}}</td> 
               <td data-sort="{{ \Carbon\Carbon::parse(strtotime($afastado['dtainisitoco']))->format('Ymd') }}">{{ \Carbon\Carbon::parse(strtotime($afastado['dtainisitoco']))->format('d/m/Y') }}</td>
               <td data-sort="{{ \Carbon\Carbon::parse(strtotime($afastado['dtafimsitoco']))->format('Ymd') }}">{{ \Carbon\Carbon::parse(strtotime($afastado['dtafimsitoco']))->format('d/m/Y') }}</td>
               <td>{{$afastado['codema']}}</td> 
               <td>{{implode(" / ", $afastado['telefones'])}}</td> 
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('javascripts_bottom')
<script>
    $(document).ready(function(){

        new DataTable('.table-afastados', {
            order: [[1, 'asc']],
            iDisplayLength: 100
        });

    });
</script>
@endsection