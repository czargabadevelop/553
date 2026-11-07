import { Head } from '@inertiajs/react';
import GuestLayout from '../../Layouts/GuestLayout';

export default function NotFound() {
  return (
    <GuestLayout>
      <Head title="404 Error Page" />

      <div className="relative z-1 flex min-h-screen flex-col items-center justify-center overflow-hidden p-6">
        {/* Common Grid Shape */}
        <div className="absolute right-0 top-0 -z-1 w-full max-w-[250px] xl:max-w-[450px]">
          <img src="/images/shape/grid-01.svg" alt="grid" />
        </div>
        <div className="absolute bottom-0 left-0 -z-1 w-full max-w-[250px] rotate-180 xl:max-w-[450px]">
          <img src="/images/shape/grid-01.svg" alt="grid" />
        </div>

        {/* Centered Content */}
        <div className="mx-auto w-full max-w-[242px] text-center sm:max-w-[472px]">
          <h1 className="mb-8 text-title-md font-bold text-gray-800 dark:text-white/90 xl:text-title-2xl">ERROR</h1>

          <img src="/images/error/404.svg" alt="404" className="dark:hidden" />
          <img src="/images/error/404-dark.svg" alt="404" className="hidden dark:block" />

          <p className="mb-6 mt-10 text-base text-gray-700 dark:text-gray-400 sm:text-lg">
            We can’t seem to find the page you are looking for!
          </p>

          <a
            href="/"
            className="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
          >
            Back to Home Page
          </a>
        </div>

        {/* Footer */}
        <p className="absolute bottom-6 left-1/2 -translate-x-1/2 text-center text-sm text-gray-500 dark:text-gray-400">
          &copy; {new Date().getFullYear()} - Card Casino
        </p>
      </div>
    </GuestLayout>
  );
}
