import { Link } from '@inertiajs/react';
import { IconChevronRight } from './Icons';

export default function Breadcrumb({ pageName }) {
  return (
    <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
      <h2 className="text-xl font-semibold text-gray-800 dark:text-white/90">{pageName}</h2>

      <nav>
        <ol className="flex items-center gap-1.5">
          <li>
            <Link
              className="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
              href="/dashboard"
            >
              Home
              <IconChevronRight className="stroke-current" />
            </Link>
          </li>
          <li className="text-sm text-gray-800 dark:text-white/90">{pageName}</li>
        </ol>
      </nav>
    </div>
  );
}
