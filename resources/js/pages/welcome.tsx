import { Head, Link, usePage } from '@inertiajs/react';
import { login } from '@/routes';
import { register } from '@/routes';
import sources from '@/routes/sheets/sources';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Sheet Parser" />
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <header className="mb-6 w-full max-w-2xl text-sm">
                    <nav className="flex items-center justify-end gap-4">
                        {auth.user ? (
                            <Link
                                href={sources.index()}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Sources
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={register()}
                                    className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </header>
                <main className="w-full max-w-2xl">
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-10 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h1 className="mb-4 text-3xl font-bold">
                            Sheet Parser
                        </h1>
                        <p className="mb-8 text-lg text-[#706f6c] dark:text-[#A1A09A]">
                            Import and transform Google Sheets into your
                            database with configurable mappings and automated
                            imports.
                        </p>
                        {auth.user ? (
                            <Link
                                href={sources.index()}
                                className="inline-flex items-center rounded-sm border border-[#19140035] bg-[#f53003] px-6 py-2 text-sm font-medium text-white hover:bg-[#e02a02] dark:border-[#FF4433]"
                            >
                                Get started
                            </Link>
                        ) : (
                            <div className="flex gap-4">
                                <Link
                                    href={login()}
                                    className="inline-flex items-center rounded-sm border border-[#19140035] px-6 py-2 text-sm font-medium text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={register()}
                                    className="inline-flex items-center rounded-sm border border-[#19140035] bg-[#f53003] px-6 py-2 text-sm font-medium text-white hover:bg-[#e02a02] dark:border-[#FF4433]"
                                >
                                    Register
                                </Link>
                            </div>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}
