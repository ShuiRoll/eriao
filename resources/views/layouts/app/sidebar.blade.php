<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

    <head>

        @include('partials.head')

        @wirekitStyles

        <style>
            .eriao-sidebar-item {
                border: 0 !important;
                outline: none !important;
                box-shadow: none !important;
                color: white !important;
            }

            .eriao-sidebar-item svg {
                color: white !important;
            }

            .eriao-sidebar-item:hover {
                background: rgba(255, 255, 255, 0.08) !important;
                color: white !important;
            }

            .eriao-sidebar-item:hover svg {
                color: white !important;
            }

            .eriao-sidebar-item[aria-current="page"],
            .eriao-sidebar-item[data-current="true"] {
                background: rgba(255, 255, 255, 0.12) !important;
                background-color: rgba(255, 255, 255, 0.12) !important;
                backdrop-filter: blur(16px) !important;
                -webkit-backdrop-filter: blur(16px) !important;
                border: 0 !important;
                border-color: transparent !important;
                outline: none !important;
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;
                color: white !important;
            }

            .eriao-sidebar-item[aria-current="page"] svg,
            .eriao-sidebar-item[data-current="true"] svg {
                color: white !important;
            }

            .eriao-sidebar-item[aria-current="page"]:hover,
            .eriao-sidebar-item[data-current="true"]:hover {
                background: rgba(255, 255, 255, 0.15) !important;
                background-color: rgba(255, 255, 255, 0.15) !important;
            }

            .eriao-profile {
                color: white !important;
            }

            .eriao-profile:hover {
                background: rgba(255, 255, 255, 0.08) !important;
            }

            .eriao-profile-avatar {
                border: 2px solid rgba(255, 255, 255, 0.95) !important;
                background: rgba(255, 255, 255, 0.10) !important;
                color: white !important;
            }
        </style>

    </head>

    <body class="min-h-screen bg-white dark:bg-zinc-950">

        <flux:sidebar
            sticky
            collapsible="mobile"
            class="border-e border-white/10 bg-primary text-white"
        >

            <flux:sidebar.header class="border-b border-white/10">

                <div class="flex w-full items-center gap-3 px-2 py-1">

                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white/30">
                        <img
                            class="size-8 object-contain"
                            src="{{ asset('images/logo_eriao.png') }}"
                            alt="ERIAO"
                        >
                    </div>

                    <div class="min-w-0 flex-1">

                        <p class="truncate text-sm font-semibold tracking-wide text-white">
                            ERIAO
                        </p>

                        <p class="mt-0.5 text-[10px] font-medium leading-4 text-white/70">
                            EXTERNAL RELATIONS AND<br>
                            INTERNATIONAL AFFAIRS OFFICE
                        </p>

                    </div>

                    <flux:sidebar.collapse
                        class="flex lg:hidden !text-white hover:!bg-white/10 hover:!text-white [&_svg]:!text-white"
                    />

                </div>

            </flux:sidebar.header>

            <flux:sidebar.nav class="px-2 py-4">

                @if(Auth::user()->role === 'admin')

                    <flux:sidebar.group
                        :heading="__('Platform')"
                        class="grid [&>[data-flux-sidebar-group-heading]]:px-3 [&>[data-flux-sidebar-group-heading]]:pb-3 [&>[data-flux-sidebar-group-heading]]:text-[11px] [&>[data-flux-sidebar-group-heading]]:font-semibold [&>[data-flux-sidebar-group-heading]]:uppercase [&>[data-flux-sidebar-group-heading]]:tracking-[0.18em] [&>[data-flux-sidebar-group-heading]]:text-white/60"
                    >

                        <flux:sidebar.item
                            icon="home"
                            :href="route('admin.dashboard')"
                            :current="request()->routeIs('admin.dashboard')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('admin.dashboard') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="presentation-chart-bar"
                            :href="route('admin.reports')"
                            :current="request()->routeIs('admin.reports')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('admin.reports') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Reports') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="shopping-cart"
                            :href="route('admin.pos')"
                            :current="request()->routeIs('admin.pos')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('admin.pos') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Point of Sale') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="document-text"
                            :href="route('admin.transactions')"
                            :current="request()->routeIs('admin.transactions')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('admin.transactions') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Transactions') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="inbox"
                            :href="route('admin.inventory')"
                            :current="request()->routeIs('admin.inventory')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('admin.inventory') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Inventory') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="shopping-bag"
                            :href="route('admin.purchase-order')"
                            :current="request()->routeIs('admin.purchase-order')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('admin.purchase-order') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Purchase Order') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="document-text"
                            :href="route('admin.college-requests')"
                            :current="request()->routeIs('admin.college-requests')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('admin.college-requests') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('College Requests') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="user-group"
                            :href="route('admin.user-management')"
                            :current="request()->routeIs('admin.user-management')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('admin.user-management') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('User Management') }}
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                @elseif (Auth::user()->role === 'employee')

                    <flux:sidebar.group
                        :heading="__('Platform')"
                        class="grid [&>[data-flux-sidebar-group-heading]]:px-3 [&>[data-flux-sidebar-group-heading]]:pb-3 [&>[data-flux-sidebar-group-heading]]:text-[11px] [&>[data-flux-sidebar-group-heading]]:font-semibold [&>[data-flux-sidebar-group-heading]]:uppercase [&>[data-flux-sidebar-group-heading]]:tracking-[0.18em] [&>[data-flux-sidebar-group-heading]]:text-white/60"
                    >

                        <flux:sidebar.item
                            icon="home"
                            :href="route('employee.dashboard')"
                            :current="request()->routeIs('employee.dashboard')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('employee.dashboard') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="presentation-chart-bar"
                            :href="route('employee.reports')"
                            :current="request()->routeIs('employee.reports')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('employee.reports') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Reports') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="document-text"
                            :href="route('employee.transactions')"
                            :current="request()->routeIs('employee.transactions')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('employee.transactions') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Transactions') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="inbox"
                            :href="route('employee.inventory')"
                            :current="request()->routeIs('employee.inventory')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('employee.inventory') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Inventory') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="shopping-bag"
                            :href="route('employee.purchase-order')"
                            :current="request()->routeIs('employee.purchase-order')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('employee.purchase-order') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Purchase Order') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="document-text"
                            :href="route('employee.college-requests')"
                            :current="request()->routeIs('employee.college-requests')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('employee.college-requests') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('College Requests') }}
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                @elseif (Auth::user()->role === 'cashier')

                    <flux:sidebar.group
                        :heading="__('Platform')"
                        class="grid [&>[data-flux-sidebar-group-heading]]:px-3 [&>[data-flux-sidebar-group-heading]]:pb-3 [&>[data-flux-sidebar-group-heading]]:text-[11px] [&>[data-flux-sidebar-group-heading]]:font-semibold [&>[data-flux-sidebar-group-heading]]:uppercase [&>[data-flux-sidebar-group-heading]]:tracking-[0.18em] [&>[data-flux-sidebar-group-heading]]:text-white/60"
                    >

                        <flux:sidebar.item
                            icon="home"
                            :href="route('cashier.dashboard')"
                            :current="request()->routeIs('cashier.dashboard')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('cashier.dashboard') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="shopping-cart"
                            :href="route('cashier.pos')"
                            :current="request()->routeIs('cashier.pos')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('cashier.pos') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Point of Sale') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="document-text"
                            :href="route('cashier.transactions')"
                            :current="request()->routeIs('cashier.transactions')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('cashier.transactions') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Transactions') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="inbox"
                            :href="route('cashier.inventory')"
                            :current="request()->routeIs('cashier.inventory')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('cashier.inventory') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Inventory') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="shopping-bag"
                            :href="route('cashier.purchase-order')"
                            :current="request()->routeIs('cashier.purchase-order')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('cashier.purchase-order') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Purchase Order') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="document-text"
                            :href="route('cashier.college-requests')"
                            :current="request()->routeIs('cashier.college-requests')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('cashier.college-requests') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('College Requests') }}
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                @elseif (Auth::user()->role === 'staff')

                    <flux:sidebar.group
                        :heading="__('Platform')"
                        class="grid [&>[data-flux-sidebar-group-heading]]:px-3 [&>[data-flux-sidebar-group-heading]]:pb-3 [&>[data-flux-sidebar-group-heading]]:text-[11px] [&>[data-flux-sidebar-group-heading]]:font-semibold [&>[data-flux-sidebar-group-heading]]:uppercase [&>[data-flux-sidebar-group-heading]]:tracking-[0.18em] [&>[data-flux-sidebar-group-heading]]:text-white/60"
                    >

                        <flux:sidebar.item
                            icon="home"
                            :href="route('staff.dashboard')"
                            :current="request()->routeIs('staff.dashboard')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('staff.dashboard') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="document-text"
                            :href="route('staff.transactions')"
                            :current="request()->routeIs('staff.transactions')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('staff.transactions') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Transactions') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="inbox"
                            :href="route('staff.inventory')"
                            :current="request()->routeIs('staff.inventory')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('staff.inventory') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Inventory') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="shopping-bag"
                            :href="route('staff.purchase-order')"
                            :current="request()->routeIs('staff.purchase-order')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('staff.purchase-order') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('Purchase Order') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="document-text"
                            :href="route('staff.college-requests')"
                            :current="request()->routeIs('staff.college-requests')"
                            wire:navigate
                            class="eriao-sidebar-item"
                            style="{{ request()->routeIs('staff.college-requests') ? 'background: rgba(255, 255, 255, 0.12) !important; background-color: rgba(255, 255, 255, 0.12) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;' : '' }}"
                        >
                            {{ __('College Requests') }}
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                @endif

            </flux:sidebar.nav>

            <flux:spacer />

            <div class="border-t border-white/10 p-3">

                <flux:dropdown position="top" align="start" class="w-full">

                    <button
                        type="button"
                        class="eriao-profile flex w-full items-center gap-3 rounded-xl px-2 py-2.5 text-left transition focus:outline-none focus:ring-0"
                    >

                    <flux:avatar initials="{{ Auth::user()->initial }}"  size="sm"/>

                        <div class="min-w-0 flex-1">

                            <p class="truncate text-sm font-semibold text-white">
                                {{ auth()->user()->first_name }}
                            </p>

                            <p class="truncate text-xs text-white/65">
                                {{ auth()->user()->email }}
                            </p>

                        </div>

                        <svg
                            class="size-5 shrink-0 text-white"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="m7 15 5 5 5-5"/>
                            <path d="m7 9 5-5 5 5"/>
                        </svg>

                    </button>

                    <flux:menu class="min-w-64">

                        <div class="px-3 py-3">

                            <div class="flex items-center gap-3">

                                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-sm font-semibold text-zinc-800">
                                    {{ auth()->user()->initials() }}
                                </div>

                                <div class="min-w-0 flex-1">

                                    <p class="truncate text-sm font-semibold text-zinc-900">
                                        {{ auth()->user()->first_name }}
                                    </p>

                                    <p class="truncate text-xs text-zinc-500">
                                        {{ auth()->user()->email }}
                                    </p>

                                </div>

                            </div>

                        </div>

                        <flux:menu.separator />

                        <flux:menu.item
                            :href="route('profile.edit')"
                            icon="cog"
                            wire:navigate
                        >
                            {{ __('Settings') }}
                        </flux:menu.item>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">

                            @csrf

                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>

                        </form>

                    </flux:menu>

                </flux:dropdown>

            </div>

        </flux:sidebar>

        <flux:header class="border-b border-white/10 bg-primary text-white lg:hidden">

            <flux:sidebar.toggle
                class="lg:hidden !text-white hover:!bg-white/10 [&_svg]:!text-white"
                icon="bars-2"
                inset="left"
            />

            <flux:spacer />

            <flux:dropdown position="top" align="end">

                <button
                    type="button"
                    class="flex items-center justify-center rounded-xl p-1.5 text-white transition hover:bg-white/10 focus:outline-none focus:ring-0"
                >

                    <div class="eriao-profile-avatar flex size-9 items-center justify-center rounded-xl text-xs font-bold">
                        {{ auth()->user()->initials() }}
                    </div>

                </button>

                <flux:menu class="min-w-64">

                    <div class="px-3 py-3">

                        <div class="flex items-center gap-3">

                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-sm font-semibold text-zinc-800">
                                {{ auth()->user()->initials() }}
                            </div>

                            <div class="min-w-0 flex-1">

                                <p class="truncate text-sm font-semibold text-zinc-900">
                                    {{ auth()->user()->first_name }}
                                </p>

                                <p class="truncate text-xs text-zinc-500">
                                    {{ auth()->user()->email }}
                                </p>

                            </div>

                        </div>

                    </div>

                    <flux:menu.separator />

                    <flux:menu.item
                        :href="route('profile.edit')"
                        icon="cog"
                        wire:navigate
                    >
                        {{ __('Settings') }}
                    </flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">

                        @csrf

                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>

                    </form>

                </flux:menu>

            </flux:dropdown>

        </flux:header>

        {{ $slot }}

        @persist('toast')

            <flux:toast.group>

                <flux:toast />

            </flux:toast.group>

        @endpersist

        @fluxScripts

        @wirekitScripts

    </body>

</html>