<!-- This file is used to store sidebar items, starting with Backpack\Base 0.9.0 -->
{{--<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>--}}

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('client') }}'><i class="nav-icon las la-user-tie"></i> Clients</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('client-balance') }}'><i class='nav-icon las la-warehouse'></i> Client balances</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('client-hystory') }}'><i class='nav-icon las la-list-ul'></i> Client hystory</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('store') }}'><i class="nav-icon las la-store-alt"></i> Stores</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('contact') }}'><i class="nav-icon las la-address-card"></i> Contacts</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('category') }}'><i class="nav-icon las la-tags"></i> Categories</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('supplier') }}'><i class="nav-icon las la-dolly"></i> Suppliers</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('nomenclature') }}'><i class="nav-icon las la-boxes"></i> Nomenclatures</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('price-type') }}'><i class='nav-icon las la-tag'></i> Price types</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('territorial-division') }}'><i class="nav-icon las la-map"></i> Territorial divisions</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('balance') }}'><i class="nav-icon las la-clipboard"></i> Balance</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('status') }}'><i class="nav-icon las la-sticky-note"></i> Clielnt statuses</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('order') }}'><i class="nav-icon las la-shopping-cart"></i> Orders</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('return-type') }}'><i class='nav-icon la la-reply'></i> Return types</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('returns') }}'><i class='nav-icon las la-undo-alt'></i> Returns</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('cash-order') }}'><i class="nav-icon las la-money-bill-wave-alt"></i> Cash orders</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('synchronization') }}'><i class='nav-icon la la-sync'></i> Synchronizations</a></li>

<li class="nav-item"><a class="nav-link" href="{{ backpack_url('elfinder') }}"><i class="nav-icon la la-files-o"></i> <span>{{ trans('backpack::crud.file_manager') }}</span></a></li>

<!-- Users, Roles, Permissions -->
<li class="nav-item nav-dropdown">
    <a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-users"></i> Authentication</a>
    <ul class="nav-dropdown-items">
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('user') }}"><i class="nav-icon la la-user"></i> <span>Users</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('role') }}"><i class="nav-icon la la-id-badge"></i> <span>Roles</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('permission') }}"><i class="nav-icon la la-key"></i> <span>Permissions</span></a></li>
    </ul>
</li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('error-log') }}'><i class='nav-icon las la-skull-crossbones'></i> Error logs</a></li>