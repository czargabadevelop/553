import { useForm } from '@inertiajs/react';

const inputClass =
  'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:text-white';
const labelClass = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';

export default function UserForm({ user, mode = 'create' }) {
  const isEdit = mode === 'edit';

  const { data, setData, post, put, processing, errors } = useForm({
    name: user?.name ?? '',
    username: user?.username ?? '',
    email: user?.email ?? '',
    role: user?.role ?? 'user',
    password: '',
    profile_picture: null,
  });

  const submit = (event) => {
    event.preventDefault();

    const options = { forceFormData: true };

    if (isEdit) {
      put(`/crud/${user.id}`, options);
    } else {
      post('/crud', options);
    }
  };

  return (
    <form onSubmit={submit} className="space-y-5">
      {Object.keys(errors).length > 0 && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
          <ul className="list-disc space-y-1 pl-5">
            {Object.values(errors).map((error, index) => (
              <li key={index}>{error}</li>
            ))}
          </ul>
        </div>
      )}

      <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
          <label htmlFor="name" className={labelClass}>
            Name
          </label>
          <input
            id="name"
            name="name"
            type="text"
            value={data.name}
            onChange={(event) => setData('name', event.target.value)}
            className={inputClass}
          />
        </div>

        <div>
          <label htmlFor="username" className={labelClass}>
            Username
          </label>
          <input
            id="username"
            name="username"
            type="text"
            value={data.username}
            onChange={(event) => setData('username', event.target.value)}
            className={inputClass}
          />
        </div>

        <div>
          <label htmlFor="email" className={labelClass}>
            Email
          </label>
          <input
            id="email"
            name="email"
            type="email"
            value={data.email}
            onChange={(event) => setData('email', event.target.value)}
            className={inputClass}
          />
        </div>

        <div>
          <label htmlFor="role" className={labelClass}>
            Role
          </label>
          <input
            id="role"
            name="role"
            type="text"
            value={data.role}
            onChange={(event) => setData('role', event.target.value)}
            className={inputClass}
          />
        </div>

        <div className="md:col-span-2">
          <label htmlFor="password" className={labelClass}>
            Password
          </label>
          <input
            id="password"
            name="password"
            type="password"
            value={data.password}
            onChange={(event) => setData('password', event.target.value)}
            className={inputClass}
          />
          {isEdit && (
            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
              Leave this empty to keep the current password.
            </p>
          )}
        </div>

        <div className="md:col-span-2">
          <label htmlFor="profile_picture" className={labelClass}>
            Profile Picture
          </label>
          {isEdit && user?.profile_picture && (
            <div className="mb-3">
              <img
                src={`/storage/${user.profile_picture}`}
                alt={`${user.name} profile picture`}
                className="h-20 w-20 rounded-full object-cover"
              />
            </div>
          )}
          <input
            id="profile_picture"
            name="profile_picture"
            type="file"
            accept="image/*"
            onChange={(event) => setData('profile_picture', event.target.files[0])}
            className="block w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 file:mr-3 file:rounded-md file:border-0 file:bg-brand-500 file:px-3 file:py-2 file:text-white dark:border-gray-700 dark:text-white"
          />
          <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {isEdit
              ? 'Optional. Upload a new image to replace the current one (max 2MB).'
              : 'Optional. Max file size: 2MB.'}
          </p>
        </div>
      </div>

      <div className="flex items-center gap-3">
        <button
          type="submit"
          disabled={processing}
          className="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-70"
        >
          {isEdit ? 'Update User' : 'Save User'}
        </button>
        <a
          href="/crud"
          className="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300"
        >
          Cancel
        </a>
      </div>
    </form>
  );
}
