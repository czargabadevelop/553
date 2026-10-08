import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '../../Layouts/GuestLayout';
import { useTheme } from '../../lib/ThemeContext';
import { IconEye, IconEyeOff, IconMoon, IconSun } from '../../Components/Icons';

function GridShapes() {
  return (
    <>
      <div className="absolute right-0 top-0 -z-1 w-full max-w-[250px] xl:max-w-[450px]">
        <img src="/images/shape/grid-01.svg" alt="grid" />
      </div>
      <div className="absolute bottom-0 left-0 -z-1 w-full max-w-[250px] rotate-180 xl:max-w-[450px]">
        <img src="/images/shape/grid-01.svg" alt="grid" />
      </div>
    </>
  );
}

const socialLinks = [
  { href: 'https://czar.dev/', label: 'Resume' },
  { href: 'https://www.linkedin.com/in/czar-gaba-255036299/', label: 'LinkedIn' },
  { href: 'https://gitlab.com/czar.gaba', label: 'GitLab' },
];

export default function Login() {
  const { isDark, toggle } = useTheme();
  const [showPassword, setShowPassword] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    username: '',
    password: '',
  });

  const submit = (event) => {
    event.preventDefault();

    post('/login', {
      onFinish: () => reset('password'),
    });
  };

  return (
    <GuestLayout>
      <Head title="Login" />

      <div className="relative z-1 bg-white p-6 dark:bg-gray-900 sm:p-0">
        <div className="relative flex h-screen w-full flex-col justify-center dark:bg-gray-900 sm:p-0 lg:flex-row">
          {/* Form */}
          <div className="flex w-full flex-1 flex-col lg:w-1/2">
            <div className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
              <div>
                <div>
                  {errors.username && <div className="mb-4 text-sm text-red-600">{errors.username}</div>}

                  <form onSubmit={submit}>
                    <div className="space-y-5">
                      {/* Username */}
                      <div>
                        <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                          Username<span className="text-error-500">*</span>
                        </label>
                        <input
                          type="text"
                          id="username"
                          name="username"
                          value={data.username}
                          onChange={(event) => setData('username', event.target.value)}
                          placeholder="username"
                          className="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:outline-hidden focus:ring-3 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                        />
                      </div>

                      {/* Password */}
                      <div>
                        <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                          Password<span className="text-error-500">*</span>
                        </label>
                        <div className="relative">
                          <input
                            name="password"
                            id="password"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            type={showPassword ? 'text' : 'password'}
                            placeholder="Enter your password"
                            className="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pl-4 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:outline-hidden focus:ring-3 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                          />
                          <span
                            onClick={() => setShowPassword((value) => !value)}
                            className="absolute right-4 top-1/2 z-30 -translate-y-1/2 cursor-pointer text-gray-500 dark:text-gray-400"
                          >
                            {showPassword ? <IconEyeOff className="fill-current" /> : <IconEye className="fill-current" />}
                          </span>
                        </div>
                      </div>

                      {/* Button */}
                      <div>
                        <button
                          type="submit"
                          disabled={processing}
                          className="shadow-theme-xs flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white transition hover:bg-brand-600 disabled:opacity-70"
                        >
                          Sign In
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <div className="relative hidden h-full w-full items-center bg-brand-950 dark:bg-white/5 lg:grid lg:w-1/2">
            <div className="z-1 flex items-center justify-center">
              <GridShapes />
              <div className="flex max-w-xs flex-col items-center">
                <a href="/" className="mb-4 block">
                  <img src="/images/xiv/xiv2.png" alt="Logo" className="dark:hidden" />
                  <img src="/images/xiv/xiv.jpg" alt="Logo" className="hidden dark:block" />
                </a>
                <p className="text-center text-gray-400 dark:text-white/60">
                  Based on TailAdmin - Tailwind CSS Admin Dashboard Template Laravel
                </p>
                <p className="text-center text-gray-400 dark:text-white/60">
                  Author:{' '}
                  <a href="https://czar.dev/" className="text-brand-500 hover:text-brand-600 dark:text-brand-400">
                    Czar
                  </a>
                </p>

                <div className="mt-10 flex flex-wrap gap-3">
                  {socialLinks.map((link) => (
                    <a
                      key={link.label}
                      href={link.href}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="rounded-full border border-black bg-black px-4 py-2 text-sm font-semibold text-white transition hover:bg-white hover:text-black dark:border-white dark:bg-white dark:text-black dark:hover:bg-black dark:hover:text-white"
                    >
                      {link.label}
                    </a>
                  ))}
                </div>
              </div>
            </div>
          </div>

          {/* Toggler */}
          <div className="fixed bottom-6 right-6 z-50 hidden sm:block">
            <button
              onClick={toggle}
              aria-label="Toggle dark mode"
              className="inline-flex size-14 items-center justify-center rounded-full bg-brand-500 text-white transition-colors hover:bg-brand-600"
            >
              <IconSun className={`fill-current ${isDark ? 'block' : 'hidden'}`} />
              <IconMoon className={`fill-current ${isDark ? 'hidden' : 'block'}`} />
            </button>
          </div>
        </div>
      </div>
    </GuestLayout>
  );
}
