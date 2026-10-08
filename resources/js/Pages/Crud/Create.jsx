import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Breadcrumb from '../../Components/Breadcrumb';
import UserForm from '../../Components/UserForm';

export default function Create({ user }) {
  return (
    <AuthenticatedLayout>
      <Head title="Create User" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <Breadcrumb pageName="Create User" />

          <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <h3 className="mb-6 text-lg font-semibold text-gray-800 dark:text-white/90">Create User</h3>

            <UserForm user={user} mode="create" />
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
