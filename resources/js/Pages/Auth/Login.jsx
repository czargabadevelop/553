import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import BrandMark from '../../Components/BrandMark';
import GuestLayout from '../../Layouts/GuestLayout';
import { useTheme } from '../../lib/ThemeContext';
import { IconEye, IconEyeOff, IconMoon, IconSun } from '../../Components/Icons';

const SUITS = [
  { symbol: '♠', color: 'text-gray-900 dark:text-white/80' },
  { symbol: '♥', color: 'text-error-500' },
  { symbol: '♦', color: 'text-error-500' },
  { symbol: '♣', color: 'text-gray-900 dark:text-white/80' },
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

  const field =
    'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:outline-hidden focus:ring-3 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800';

  return (
    <GuestLayout>
      <Head title="Sign in" />

      <div className="relative min-h-screen bg-white dark:bg-gray-900">
        <div className="flex min-h-screen w-full flex-col lg:flex-row">
          {/* Sign in form */}
          <div className="flex w-full flex-1 flex-col px-6 py-10 lg:w-1/2 lg:px-12">
            <div className="flex items-center justify-between">
              <BrandMark />
              <button
                type="button"
                onClick={toggle}
                aria-label="Toggle dark mode"
                className="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800"
              >
                <IconSun className={`h-5 w-5 fill-current ${isDark ? 'block' : 'hidden'}`} />
                <IconMoon className={`h-5 w-5 fill-current ${isDark ? 'hidden' : 'block'}`} />
              </button>
            </div>

            <div className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-10">
              <div className="mb-8">
                <h1 className="text-2xl font-semibold text-gray-800 dark:text-white/90">Welcome back</h1>
                <p className="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">
                  Sign in to take your seat at the table.
                </p>
              </div>

              {errors.username && (
                <div className="mb-4 rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-theme-sm text-error-600 dark:bg-error-500/10">
                  {errors.username}
                </div>
              )}

              <form onSubmit={submit}>
                <div className="space-y-5">
                  <div>
                    <label
                      htmlFor="username"
                      className="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400"
                    >
                      Username
                    </label>
                    <input
                      type="text"
                      id="username"
                      name="username"
                      autoComplete="username"
                      value={data.username}
                      onChange={(event) => setData('username', event.target.value)}
                      placeholder="Your username"
                      className={field}
                    />
                  </div>

                  <div>
                    <label
                      htmlFor="password"
                      className="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400"
                    >
                      Password
                    </label>
                    <div className="relative">
                      <input
                        name="password"
                        id="password"
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        type={showPassword ? 'text' : 'password'}
                        placeholder="Your password"
                        className={`${field} pr-11`}
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword((value) => !value)}
                        aria-label={showPassword ? 'Hide password' : 'Show password'}
                        className="absolute right-4 top-1/2 z-30 -translate-y-1/2 cursor-pointer text-gray-500 dark:text-gray-400"
                      >
                        {showPassword ? <IconEyeOff className="fill-current" /> : <IconEye className="fill-current" />}
                      </button>
                    </div>
                  </div>

                  <button
                    type="submit"
                    disabled={processing}
                    className="shadow-theme-xs flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-3 text-theme-sm font-semibold text-white transition hover:bg-brand-600 disabled:opacity-70"
                  >
                    {processing ? 'Signing in…' : 'Sign In'}
                  </button>
                </div>
              </form>

              <p className="mt-8 text-center text-theme-xs text-gray-400 dark:text-gray-500">
                Four-player Pepito · 13-card Chinese poker
              </p>
            </div>
          </div>

          {/* Brand panel */}
          <div className="relative hidden w-1/2 items-center justify-center overflow-hidden bg-brand-950 lg:flex dark:bg-white/5">
            <div className="absolute inset-0 opacity-20">
              <div className="absolute -left-16 top-10 h-72 w-52 rotate-12 rounded-2xl border border-white/40" />
              <div className="absolute right-10 top-1/3 h-72 w-52 -rotate-6 rounded-2xl border border-white/30" />
              <div className="absolute bottom-8 left-1/3 h-72 w-52 rotate-3 rounded-2xl border border-white/20" />
            </div>

            <div className="relative z-1 flex max-w-sm flex-col items-center px-10 text-center">
              <div className="mb-6 flex items-center justify-center gap-3 text-4xl">
                {SUITS.map((suit) => (
                  <span key={suit.symbol} className={suit.color}>
                    {suit.symbol}
                  </span>
                ))}
              </div>

              <h2 className="text-3xl font-semibold tracking-tight text-white">
                Card<span className="text-brand-400">Casino</span>
              </h2>
              <p className="mt-3 text-theme-sm text-white/70">
                Four players. Thirteen cards each. Arrange your back, middle and front hands and let the
                table settle itself.
              </p>

              <div className="mt-8 grid w-full grid-cols-3 gap-3 text-white/80">
                <div className="rounded-xl border border-white/15 bg-white/5 px-3 py-3">
                  <p className="text-lg font-semibold">5</p>
                  <p className="text-theme-xs uppercase tracking-wide text-white/60">Back</p>
                </div>
                <div className="rounded-xl border border-white/15 bg-white/5 px-3 py-3">
                  <p className="text-lg font-semibold">5</p>
                  <p className="text-theme-xs uppercase tracking-wide text-white/60">Middle</p>
                </div>
                <div className="rounded-xl border border-white/15 bg-white/5 px-3 py-3">
                  <p className="text-lg font-semibold">3</p>
                  <p className="text-theme-xs uppercase tracking-wide text-white/60">Front</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </GuestLayout>
  );
}
