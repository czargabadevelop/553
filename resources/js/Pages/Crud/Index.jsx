import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Breadcrumb from '../../Components/Breadcrumb';
import FlashMessage from '../../Components/FlashMessage';

function decodeLabel(label) {
  if (typeof document === 'undefined') {
    return label;
  }

  const el = document.createElement('div');
  el.innerHTML = label;

  return el.textContent;
}

function Pagination({ links }) {
  const items = links.filter((link, index) => {
    // Keep the previous/next links plus page numbers, dropping the "..." spacers.
    return index === 0 || index === links.length - 1 || link.label !== '...';
  });

  if (items.length <= 3) {
    return null;
  }

  return (
    <div className="mt-6 flex flex-wrap items-center gap-1">
      {items.map((link, index) => (
        <Link
          key={index}
          href={link.url ?? '#'}
          preserveScroll
          className={`rounded-lg border px-3 py-2 text-sm ${
            link.active
              ? 'border-brand-500 bg-brand-500 text-white'
              : link.url
                ? 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]'
                : 'cursor-not-allowed border-gray-200 text-gray-400 dark:border-gray-800'
          }`}
        >
          {decodeLabel(link.label)}
        </Link>
      ))}
    </div>
  );
}

export default function Index({ users }) {
  const { auth, flash } = usePage().props;
  const isAdmin = auth?.user?.role === 'admin';

  const destroy = (user) => {
    if (confirm('Delete this user?')) {
      router.delete(`/crud/${user.id}`);
    }
  };

  return (
    <AuthenticatedLayout>
      <Head title="Users" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <Breadcrumb pageName="Users" />

          <FlashMessage message={flash?.success} />

          <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h3 className="text-lg font-semibold text-gray-800 dark:text-white/90">Users</h3>
                <p className="text-sm text-gray-500 dark:text-gray-400">Basic CRUD listing for the users table.</p>
              </div>

              {isAdmin && (
                <Link
                  href="/crud/create"
                  className="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600"
                >
                  Add User
                </Link>
              )}
            </div>

            <div className="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                  <thead className="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                      {['Photo', 'Name', 'Username', 'Email', 'Role'].map((heading) => (
                        <th
                          key={heading}
                          className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                          {heading}
                        </th>
                      ))}
                      <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Actions
                      </th>
                    </tr>
                  </thead>

                  <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-transparent">
                    {users.data.length === 0 && (
                      <tr>
                        <td colSpan={6} className="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                          No users found.
                        </td>
                      </tr>
                    )}

                    {users.data.map((user) => (
                      <tr key={user.id}>
                        <td className="px-4 py-4 text-sm text-gray-800 dark:text-gray-100">
                          {user.profile_picture ? (
                            <img
                              src={`/storage/${user.profile_picture}`}
                              alt={`${user.name} profile picture`}
                              className="h-10 w-10 rounded-full object-cover"
                            />
                          ) : (
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                              {user.name?.charAt(0).toUpperCase()}
                            </div>
                          )}
                        </td>
                        <td className="px-4 py-4 text-sm text-gray-800 dark:text-gray-100">{user.name}</td>
                        <td className="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{user.username}</td>
                        <td className="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{user.email}</td>
                        <td className="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{user.role}</td>
                        <td className="px-4 py-4 text-right text-sm">
                          <div className="inline-flex items-center gap-2">
                            <Link
                              href={`/crud/${user.id}`}
                              className="rounded-lg border border-gray-300 px-3 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]"
                            >
                              View
                            </Link>
                            <Link
                              href={`/crud/${user.id}/edit`}
                              className="rounded-lg border border-brand-500 px-3 py-2 text-brand-500 hover:bg-brand-50 dark:hover:bg-brand-500/10"
                            >
                              Edit
                            </Link>
                            <button
                              onClick={() => destroy(user)}
                              className="rounded-lg border border-red-500 px-3 py-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10"
                            >
                              Delete
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>

            <Pagination links={users.links} />
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
