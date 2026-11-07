import { useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import BrandMark from './BrandMark';
import { useTheme } from '../lib/ThemeContext';
import useClickOutside from '../lib/useClickOutside';
import {
  IconChevronDown,
  IconClose,
  IconHamburger,
  IconLogout,
  IconMenuDots,
  IconMenuLines,
  IconMoon,
  IconSearch,
  IconSun,
  IconUsers,
} from './Icons';

export default function Header({ sidebarToggle, onToggleSidebar }) {
  const { auth } = usePage().props;
  const user = auth?.user;
  const { isDark, toggle } = useTheme();

  const [menuToggle, setMenuToggle] = useState(false);
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [searchValue, setSearchValue] = useState('');

  const dropdownRef = useRef(null);
  const searchRef = useRef(null);

  useClickOutside(dropdownRef, () => setDropdownOpen(false), dropdownOpen);

  // Cmd/Ctrl + K focuses the search field, matching the Blade behavior.
  useEffect(() => {
    function onKeyDown(event) {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchRef.current?.focus();
      }
    }

    document.addEventListener('keydown', onKeyDown);

    return () => document.removeEventListener('keydown', onKeyDown);
  }, []);

  const avatar = user?.profile_picture ? `/storage/${user.profile_picture}` : null;
  const initials = (user?.name || user?.username || '?')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('');

  const logout = () => {
    router.post('/logout');
  };

  return (
    <header className="sticky top-0 z-99999 flex w-full border-gray-200 bg-white lg:border-b dark:border-gray-800 dark:bg-gray-900">
      <div className="flex grow flex-col items-center justify-between lg:flex-row lg:px-6">
        <div className="flex w-full items-center justify-between gap-2 border-b border-gray-200 px-3 py-3 sm:gap-4 lg:justify-normal lg:border-b-0 lg:px-0 lg:py-4 dark:border-gray-800">
          {/* Hamburger Toggle BTN */}
          <button
            onClick={onToggleSidebar}
            className={`z-99999 flex h-10 w-10 items-center justify-center rounded-lg border-gray-200 text-gray-500 lg:h-11 lg:w-11 lg:border dark:border-gray-800 dark:text-gray-400 ${
              sidebarToggle ? 'bg-gray-100 dark:bg-gray-800 lg:bg-transparent dark:lg:bg-transparent' : ''
            }`}
          >
            <IconHamburger className="hidden fill-current lg:block" />

            <IconMenuLines className={`fill-current lg:hidden ${sidebarToggle ? 'hidden' : 'block'}`} />

            {/* cross icon */}
            <IconClose className={`fill-current ${sidebarToggle ? 'block lg:hidden' : 'hidden'}`} />
          </button>
          {/* Hamburger Toggle BTN */}

          <Link href="/dashboard" className="lg:hidden">
            <BrandMark />
          </Link>

          {/* Application nav menu button */}
          <button
            onClick={() => setMenuToggle((value) => !value)}
            className={`z-99999 flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 lg:hidden dark:text-gray-400 dark:hover:bg-gray-800 ${
              menuToggle ? 'bg-gray-100 dark:bg-gray-800' : ''
            }`}
          >
            <IconMenuDots className="fill-current" />
          </button>
          {/* Application nav menu button */}

          <div className="hidden lg:block">
            <form onSubmit={(event) => event.preventDefault()}>
              <div className="relative">
                <span className="absolute left-4 top-1/2 -translate-y-1/2">
                  <IconSearch className="fill-gray-500 dark:fill-gray-400" />
                </span>
                <input
                  ref={searchRef}
                  type="text"
                  value={searchValue}
                  onChange={(event) => setSearchValue(event.target.value)}
                  placeholder="Search or type command..."
                  id="search-input"
                  className="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-200 bg-transparent py-2.5 pl-12 pr-14 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-[430px] dark:border-gray-800 dark:bg-gray-900 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30"
                />
              </div>
            </form>
          </div>
        </div>

        <div
          className={`shadow-theme-md w-full items-center justify-between gap-4 px-5 py-4 lg:flex lg:justify-end lg:px-0 lg:shadow-none ${
            menuToggle ? 'flex' : 'hidden'
          }`}
        >
          <div className="flex items-center gap-2 2xsm:gap-3">
            {/* Dark Mode Toggler */}
            <button
              onClick={toggle}
              aria-label="Toggle dark mode"
              className="hover:text-dark-900 relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            >
              <IconSun className={`hidden fill-current ${isDark ? 'block' : ''}`} />
              <IconMoon className={`fill-current ${isDark ? 'hidden' : ''}`} />
            </button>
            {/* Dark Mode Toggler */}
          </div>

          {/* User Area */}
          <div ref={dropdownRef} className="relative">
            <button
              onClick={() => setDropdownOpen((value) => !value)}
              className="flex items-center text-gray-700 dark:text-gray-400"
            >
              <span className="mr-3 flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-brand-500 text-theme-sm font-semibold text-white">
                {avatar ? <img src={avatar} alt="User" className="h-full w-full object-cover" /> : initials}
              </span>

              <span className="text-theme-sm mr-1 block font-medium">
                {user?.username ?? 'USERNAME'}
              </span>

              <IconChevronDown
                className={`stroke-gray-500 dark:stroke-gray-400 ${dropdownOpen ? 'rotate-180' : ''}`}
              />
            </button>

            {/* Dropdown Start */}
            {dropdownOpen && (
              <div className="shadow-theme-lg dark:bg-gray-dark absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800">
                <div>
                  <span className="text-theme-sm block font-medium text-gray-700 dark:text-gray-400">
                    {user?.name ?? 'NAME'}
                  </span>
                  <span className="text-theme-xs mt-0.5 block text-gray-500 dark:text-gray-400">
                    {user?.email ?? 'EMAIL'}
                  </span>
                </div>

                <ul className="flex flex-col gap-1 border-b border-gray-200 pb-3 pt-4 dark:border-gray-800">
                  {user && (
                    <li>
                      <Link
                        href="/profile"
                        className="group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                      >
                        <IconUsers className="fill-gray-500 group-hover:fill-gray-700 dark:fill-gray-400 dark:group-hover:fill-gray-300" />
                        Edit profile
                      </Link>
                    </li>
                  )}
                </ul>

                <button
                  onClick={logout}
                  className="group text-theme-sm mt-3 flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                >
                  <IconLogout className="fill-gray-500 group-hover:fill-gray-700 dark:group-hover:fill-gray-300" />
                  Logout
                </button>
              </div>
            )}
            {/* Dropdown End */}
          </div>
          {/* User Area */}
        </div>
      </div>
    </header>
  );
}
