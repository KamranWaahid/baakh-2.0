@extends('adminlte::page')

@section('title', 'Baakh Admins')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1 class="m-0 text-dark">Baakh Admins</h1>
        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-default"><i class="fa fa-plus mr-2"></i> Add New Admin</a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Picture</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>WhatsApp</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
                                @foreach ($profiles as $key => $user)
                                <tr>
                                    <td scope="row">{{ $key+1; }}1</td>
                                    <td><img src="{{ file_exists($user->avatar) ? asset($user->avatar) : asset('assets/img/placeholder290x293.jpg') }}" class="rounded-circle" width="50px" alt=""></td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->whatsapp }}</td>
                                    <td>{{ $user->role }}</td>
                                    <td>
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                                        <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
@stop
