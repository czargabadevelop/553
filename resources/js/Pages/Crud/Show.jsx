import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Breadcrumb from '../../Components/Breadcrumb';

export default function Show({ user }) {
  const fields = [
    { label: 'Name', value: user.name },
    { label: 'Username', value: user.username },
    { label: 'Email', value: user.email },
    { label: 'Role', value: user.role },
  ];

  return (
    <AuthenticatedLayout>
      <Head title="User Details" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <Breadcrumb pageName="User Details" />

          <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <div className="mb-6 flex items-center justify-between">
              <h3 className="text-lg font-semibold text-gray-800 dark:text-white/90">User Details</h3>
              <Link
                href={`/crud/${user.id}/edit`}
                className="rounded-lg border border-brand-500 px-4 py-2 text-sm font-medium text-brand-500"
              >
                Edit
              </Link>
            </div>

            <div className="mb-6">
              {user.profile_picture ? (
                <img
                  src={`/storage/${user.profile_picture}`}
                  alt={`${user.name} profile picture`}
                  className="h-24 w-24 rounded-full object-cover"
                />
              ) : (
                <div className="flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 text-2xl font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                  {user.name?.charAt(0).toUpperCase()}
                </div>
              )}
            </div>

            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
              {fields.map((field) => (
                <div key={field.label}>
                  <p className="mb-2 text-xs text-gray-500 dark:text-gray-400">{field.label}</p>
                  <p className="text-sm font-medium text-gray-800 dark:text-white/90">{field.value}</p>
                </div>
              ))}
            </div>

            <div className="mt-6">
              <Link href="/crud" className="text-sm font-medium text-brand-500 hover:text-brand-600">
                Back to list
              </Link>
            </div>
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
