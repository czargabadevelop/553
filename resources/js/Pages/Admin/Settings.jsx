import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function AdminSettings({ settings, flags }) {
  const settingsForm = useForm({
    settings: Object.fromEntries(settings.map((setting) => [setting.key, setting.value ?? ''])),
    reason: '',
  });

  const flagsForm = useForm({
    flags: Object.fromEntries(flags.map((flag) => [flag.key, flag.enabled])),
  });

  const saveSettings = (event) => {
    event.preventDefault();
    settingsForm.patch('/admin/settings', { preserveScroll: true });
  };

  const saveFlags = (event) => {
    event.preventDefault();
    flagsForm.post('/admin/settings/flags', { preserveScroll: true });
  };

  const groups = settings.reduce((accumulator, setting) => {
    accumulator[setting.group] = accumulator[setting.group] ?? [];
    accumulator[setting.group].push(setting);
    return accumulator;
  }, {});

  return (
    <AuthenticatedLayout>
      <Head title="Settings" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <h1 className="mb-6 text-xl font-semibold text-gray-800 dark:text-white/90">Settings</h1>

          <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <form onSubmit={saveSettings} className="xl:col-span-2 space-y-6">
              {Object.entries(groups).map(([group, items]) => (
                <section key={group} className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                  <h2 className="mb-4 text-lg font-semibold capitalize text-gray-800 dark:text-white/90">{group}</h2>
                  <div className="space-y-3">
                    {items.map((setting) => (
                      <div key={setting.key} className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                          <p className="text-theme-sm font-medium text-gray-800 dark:text-white/90">{setting.key}</p>
                          <p className="text-theme-xs text-gray-500 dark:text-gray-400">{setting.description ?? setting.type}</p>
                        </div>
                        <input
                          value={settingsForm.data.settings[setting.key] ?? ''}
                          onChange={(event) => settingsForm.setData('settings', { ...settingsForm.data.settings, [setting.key]: event.target.value })}
                          className="w-64 rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        />
                      </div>
                    ))}
                  </div>
                </section>
              ))}

              <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <label className="block text-theme-xs text-gray-500 dark:text-gray-400">
                  Reason for change (audited)
                  <input
                    value={settingsForm.data.reason}
                    onChange={(event) => settingsForm.setData('reason', event.target.value)}
                    className="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                  />
                </label>
                <button type="submit" disabled={settingsForm.processing} className="mt-4 rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white">Save settings</button>
              </section>
            </form>

            <form onSubmit={saveFlags} className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Feature flags</h2>
              <p className="mb-4 text-theme-xs text-gray-500 dark:text-gray-400">
                Real-money features are off by default and enforced by the backend, not the UI.
              </p>

              <div className="space-y-4">
                {flags.map((flag) => (
                  <label key={flag.key} className="flex items-start justify-between gap-3">
                    <span>
                      <span className="block text-theme-sm font-medium capitalize text-gray-800 dark:text-white/90">{flag.key.replaceAll('_', ' ')}</span>
                      <span className="block text-theme-xs text-gray-500 dark:text-gray-400">{flag.description}</span>
                    </span>
                    <input
                      type="checkbox"
                      checked={Boolean(flagsForm.data.flags[flag.key])}
                      onChange={(event) => flagsForm.setData('flags', { ...flagsForm.data.flags, [flag.key]: event.target.checked })}
                    />
                  </label>
                ))}
              </div>

              <button type="submit" disabled={flagsForm.processing} className="mt-5 rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white">Save flags</button>
            </form>
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
