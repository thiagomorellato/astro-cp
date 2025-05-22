@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Cash Shop</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('cash.shop.import') }}" method="POST">
        @csrf
        <button class="btn btn-primary mb-3">Atualizar items_cash_db</button>
    </form>

    @if(count($items) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>AegisName</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item[0] }}</td>
                        <td>{{ $item[1] }}</td>
                        <td>{{ $item[2] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Nenhum item encontrado no CSV.</p>
    @endif
</div>
@endsection
