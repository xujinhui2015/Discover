@if($user)
<li class="dropdown dropdown-user nav-item">
    <a class="dropdown-toggle nav-link dropdown-user-link" href="#" data-toggle="dropdown">
        <div class="user-info-container">
            <img class="round" src="{{ $user->getAvatar() }}" alt="avatar" height="32" width="32" />
            <div class="user-name-status d-sm-block d-none">
                <div class="user-name">{{ $user->name }}</div>
                <div class="user-status"><i class="fa fa-circle"></i> {{ trans('admin.online') }}</div>
            </div>
        </div>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <a href="{{ admin_url('personal-config') }}" class="dropdown-item">
            <i class="feather icon-user"></i> 个人设置
        </a>
        @if($user->isAdministrator())
            <a href="{{ admin_url('system-config') }}" class="dropdown-item">
                <i class="feather icon-settings"></i> 系统设置
            </a>
        @endif

        <div class="dropdown-divider"></div>

        <a class="dropdown-item" href="{{ admin_url('auth/logout') }}">
            <i class="feather icon-power"></i> {{ trans('admin.logout') }}
        </a>
    </div>
</li>
@endif
