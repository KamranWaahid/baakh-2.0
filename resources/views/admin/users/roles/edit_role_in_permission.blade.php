@extends('adminlte::page')

@section('title', 'Add Permissions in Role')

@section('content_header')
    <h1 class="m-0 text-dark">
        <a href="{{ route('role.permissions.index') }}" class="btn"><i class="fa fa-chevron-left"></i></a>
        Assing Permissions to a Role
    </h1>
@stop

@section('content')
    <div class="row">
        <div class="col-9 m-auto">
            <div class="card">
                <form action="{{ route('role.permission.update', $role->id) }}" method="post">
                    @csrf
                    @method('put')
                    <div class="card-body">
                        <div class="text-center">
                            <h4 class="text-primary">Update {{ $role->name; }} Role</h4>
                        </div>
                        <hr>
                        <div class="form-group mt-3">
                            <input type="checkbox" class="form-check-select" id="select_all_permissions">
                            <label for="select_all_permissions" style="user-select: none">Select All</label>
                        </div>
                        <hr>
                        @foreach ($permission_groups as $group)
                            <div class="row ml-2">
                                <div class="col-3 groups">
                                @php
                                    $permissions = App\Models\User::getPermissionsByGroupName($group->group_name)
                                @endphp
                                    <div class="form-group">
                                        <input type="checkbox" 
                                            class="form-check-select prms prms-grp" 
                                            id="group_{{ $group->group_name }}"
                                            {{ App\Models\User::roleHasPermissions($role, $permissions) ? 'checked' : '' }}
                                            />
                                        <label for="group_{{ $group->group_name }}" style="user-select: none">{{ Str::ucfirst($group->group_name) }}</label>
                                    </div>
                                </div>
                                <div class="col-9 permissions">
                                   
                                    @foreach ($permissions as $permission)
                                        <div class="sgroup">
                                            <input type="checkbox" 
                                                class="form-check-select prms group_{{ $group->group_name }}" 
                                                name="permission[]" value="{{ $permission->id }}" 
                                                id="permission_{{ $permission->id }}" 
                                                {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                            />
                                            <label for="permission_{{ $permission->id }}" style="user-select: none">{{ Str::ucfirst($permission->name) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <hr>
                        @endforeach
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success"><i class="fa fa-save mr-2"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop




@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('js')
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/summernote/summernote-bs4.min.js') }}"></script>

<script>
    $(function () {
        $('.select2').select2();
        $('#select_all_permissions').on('click', function () {
            if($(this).is(':checked'))
            {
                $('.prms').prop('checked', true);
            }else{
                $('.prms').prop('checked', false);
            }
        })

        $('.prms-grp').on('click', function () {
            var group_id = $(this).attr('id');
            if($('#'+group_id).is(':checked'))
            {
                $('.'+group_id).prop('checked', true);
            }else{
                $('.'+group_id).prop('checked', false);
            }
        })


    })
</script>
@endsection