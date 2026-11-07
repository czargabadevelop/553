import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function Profile({ user, profile }) {
  const form = useForm({
    name: user.name,
    email: user.email,
    profile: {
      display_name: profile.display_name ?? '',
      date_of_birth: profile.date_of_birth ?? '',
      country_code: profile.country_code ?? '',
      phone: profile.phone ?? '',
      deposit_limit: profile.deposit_limit ?? '',
      session_limit_minutes: profile.session_limit_minutes ?? '',
      self_excluded: profile.self_excluded,
    },
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  const submit = (event) => {
    event.preventDefault();
    form.patch('/profile', { preserveScroll: true });
  };

  const field = 'w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white';

  return (
    <AuthenticatedLayout>
      <Head title="Profile" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <h1 className="mb-6 text-xl font-semibold text-gray-800 dark:text-white/90">Profile & account security</h1>

          <form onSubmit={submit} className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Account</h2>
              <div className="space-y-3">
                <label className="block text-theme-xs text-gray-500 dark:text-gray-400">
                  Name
                  <input className={field} value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                </label>
                <label className="block text-theme-xs text-gray-500 dark:text-gray-400">
                  Email
                  <input className={field} value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} />
                </label>
                <p className="text-theme-xs text-gray-500 dark:text-gray-400">
                  Username: <span className="font-medium text-gray-700 dark:text-gray-200">{user.username}</span> · Status: {user.account_status} · KYC: {user.verification_status.replace('_', ' ')}
                </p>
              </div>

              <h3 className="mb-3 mt-6 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Change password</h3>
              <div className="space-y-3">
                <input type="password" className={field} placeholder="Current password" value={form.data.current_password} onChange={(event) => form.setData('current_password', event.target.value)} />
                <input type="password" className={field} placeholder="New password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} />
                <input type="password" className={field} placeholder="Confirm new password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} />
              </div>
            </section>

            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Profile & responsible gaming</h2>
              <div className="space-y-3">
                <input className={field} placeholder="Display name" value={form.data.profile.display_name} onChange={(event) => form.setData('profile', { ...form.data.profile, display_name: event.target.value })} />
                <input type="date" className={field} value={form.data.profile.date_of_birth} onChange={(event) => form.setData('profile', { ...form.data.profile, date_of_birth: event.target.value })} />
                <input className={field} placeholder="Country code (PH)" maxLength={2} value={form.data.profile.country_code} onChange={(event) => form.setData('profile', { ...form.data.profile, country_code: event.target.value })} />
                <input className={field} placeholder="Phone" value={form.data.profile.phone} onChange={(event) => form.setData('profile', { ...form.data.profile, phone: event.target.value })} />
                <input className={field} placeholder="Deposit limit per day (PHP)" value={form.data.profile.deposit_limit} onChange={(event) => form.setData('profile', { ...form.data.profile, deposit_limit: event.target.value })} />
                <input type="number" min="1" max="1440" className={field} placeholder="Session limit (minutes)" value={form.data.profile.session_limit_minutes} onChange={(event) => form.setData('profile', { ...form.data.profile, session_limit_minutes: event.target.value })} />

                <label className="flex items-center gap-2 text-theme-sm text-gray-700 dark:text-gray-300">
                  <input
                    type="checkbox"
                    checked={form.data.profile.self_excluded}
                    onChange={(event) => form.setData('profile', { ...form.data.profile, self_excluded: event.target.checked })}
                  />
                  Self-exclude my account from play
                </label>
              </div>

              <button type="submit" disabled={form.processing} className="mt-5 rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-brand-600">
                Save changes
              </button>
            </section>
          </form>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
