<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sign Up - ERIAO</title>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @wirekitStyles()
</head>
<body class="flex flex-col w-screen h-screen text-zinc-700">
    <div class="grid grid-cols-3 w-full h-full">
        <div class="flex flex-col col-span-1">
            <livewire:authentication.sign-up-form />
        </div>

        <div class="relative flex flex-col col-span-2 bg-primary w-full h-full overflow-hidden">
            <div class="absolute inset-0 opacity-[0.08]">
                <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,0.7)_1px,transparent_1px),linear-gradient(rgba(255,255,255,0.7)_1px,transparent_1px)] bg-[size:48px_48px]"></div>
            </div>

            <div class="absolute -top-32 -right-32 w-[32rem] h-[32rem] rounded-full border border-white/10"></div>
            <div class="absolute -top-20 -right-20 w-[24rem] h-[24rem] rounded-full border border-white/10"></div>
            <div class="absolute top-16 right-16 w-40 h-40 rounded-full border border-white/10"></div>

            <div class="absolute -bottom-40 -left-40 w-[34rem] h-[34rem] rounded-full border border-white/10"></div>
            <div class="absolute -bottom-24 -left-24 w-[24rem] h-[24rem] rounded-full border border-white/10"></div>

            <div class="absolute top-0 left-0 w-full h-full">
                <div class="absolute top-[18%] left-[12%] w-24 h-24 border border-white/10 rotate-12"></div>
                <div class="absolute top-[21%] left-[15%] w-16 h-16 border border-white/10 rotate-12"></div>

                <div class="absolute bottom-[22%] right-[15%] w-28 h-28 border border-white/10 -rotate-12"></div>
                <div class="absolute bottom-[25%] right-[18%] w-16 h-16 border border-white/10 -rotate-12"></div>
            </div>

            <div class="relative z-10 flex flex-col items-center justify-center w-full h-full px-12 text-white">
                <div class="max-w-3xl text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 mb-8 rounded-2xl border border-white/20 bg-white/10 backdrop-blur-sm">
                        <svg
                            class="w-10 h-10"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M4 7.5 12 4l8 3.5L12 11 4 7.5Z"/>
                            <path d="M6 9.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3V9.5"/>
                            <path d="M20 8v6"/>
                        </svg>
                    </div>

                    <p class="mb-4 text-sm font-medium uppercase tracking-[0.35em] text-white/70">
                        ERIAO
                    </p>

                    <h1 class="text-5xl font-semibold tracking-tight leading-tight xl:text-6xl">
                        University of Mindanao
                        <span class="block text-white/80">
                            Management System
                        </span>
                    </h1>

                    <p class="max-w-2xl mx-auto mt-7 text-base leading-7 text-white/65 xl:text-lg">
                        A centralized platform for managing university merchandise,
                        inventory, sales, and daily retail operations.
                    </p>

                    <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto mt-12">
                        <div class="p-5 text-left rounded-2xl border border-white/10 bg-white/10 backdrop-blur-sm">
                            <div class="flex items-center justify-center w-10 h-10 mb-4 rounded-xl bg-white/10">
                                <svg
                                    class="w-5 h-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M3 7.5 12 3l9 4.5"/>
                                    <path d="M3 7.5V17l9 4 9-4V7.5"/>
                                    <path d="M12 12v9"/>
                                    <path d="m3 7.5 9 4.5 9-4.5"/>
                                </svg>
                            </div>

                            <p class="text-sm font-semibold">
                                Inventory
                            </p>

                            <p class="mt-1 text-xs leading-5 text-white/55">
                                Manage products and stock levels.
                            </p>
                        </div>

                        <div class="p-5 text-left rounded-2xl border border-white/10 bg-white/10 backdrop-blur-sm">
                            <div class="flex items-center justify-center w-10 h-10 mb-4 rounded-xl bg-white/10">
                                <svg
                                    class="w-5 h-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <rect x="4" y="3" width="16" height="18" rx="2"/>
                                    <path d="M8 7h8"/>
                                    <path d="M8 11h8"/>
                                    <path d="M8 15h3"/>
                                    <path d="M14 15h2"/>
                                </svg>
                            </div>

                            <p class="text-sm font-semibold">
                                Point of Sale
                            </p>

                            <p class="mt-1 text-xs leading-5 text-white/55">
                                Process merchandise transactions.
                            </p>
                        </div>

                        <div class="p-5 text-left rounded-2xl border border-white/10 bg-white/10 backdrop-blur-sm">
                            <div class="flex items-center justify-center w-10 h-10 mb-4 rounded-xl bg-white/10">
                                <svg
                                    class="w-5 h-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M4 19V5"/>
                                    <path d="M4 19h16"/>
                                    <path d="M8 16v-5"/>
                                    <path d="M12 16V8"/>
                                    <path d="M16 16v-7"/>
                                    <path d="M20 16v-3"/>
                                </svg>
                            </div>

                            <p class="text-sm font-semibold">
                                Reports
                            </p>

                            <p class="mt-1 text-xs leading-5 text-white/55">
                                Monitor sales and operations.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="absolute bottom-10 left-0 flex items-center justify-center w-full">
                    <div class="flex items-center gap-3 text-xs tracking-wide text-white/40">
                        <span class="w-8 h-px bg-white/20"></span>
                        <span>UNIVERSITY COMMERCE</span>
                        <span class="w-8 h-px bg-white/20"></span>
                    </div>
                </div>
            </div>

            <div class="absolute top-10 left-10 w-3 h-3 rounded-full bg-white/20"></div>
            <div class="absolute top-10 right-10 w-3 h-3 rounded-full bg-white/20"></div>
            <div class="absolute bottom-10 left-10 w-3 h-3 rounded-full bg-white/20"></div>
            <div class="absolute bottom-10 right-10 w-3 h-3 rounded-full bg-white/20"></div>
        </div>
    </div>

    <x-wirekit::toast-region />
    
    @livewireScripts()
    @fluxScripts()
    @wirekitScripts()
</body>
</html>