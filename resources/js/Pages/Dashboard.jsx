import { useRef, useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';
import ApexChart from '../Components/ApexChart';
import MetricGroup from '../Components/MetricGroup';
import RecentOrdersTable from '../Components/RecentOrdersTable';
import WorldMap from '../Components/WorldMap';
import { IconDotsVertical } from '../Components/Icons';
import useClickOutside from '../lib/useClickOutside';
import { chartOneOptions, chartThreeOptions, chartTwoOptions } from '../charts/options';

/**
 * The dropdown menu shown in the corner of each dashboard card.
 */
function CardMenu() {
  const [open, setOpen] = useState(false);
  const ref = useRef(null);

  useClickOutside(ref, () => setOpen(false), open);

  return (
    <div ref={ref} className="relative h-fit">
      <button
        onClick={() => setOpen((value) => !value)}
        className={open ? 'text-gray-700 dark:text-white' : 'text-gray-400 hover:text-gray-700 dark:hover:text-white'}
      >
        <IconDotsVertical className="fill-current" />
      </button>

      {open && (
        <div className="shadow-theme-lg dark:bg-gray-dark absolute right-0 top-full z-40 w-40 space-y-1 rounded-2xl border border-gray-200 bg-white p-2 dark:border-gray-800">
          <button className="text-theme-xs flex w-full rounded-lg px-3 py-2 text-left font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
            View More
          </button>
          <button className="text-theme-xs flex w-full rounded-lg px-3 py-2 text-left font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
            Delete
          </button>
        </div>
      )}
    </div>
  );
}

const statisticsTabs = ['overview', 'sales', 'revenue'];

function MonthlyTargetStats({ label, value, up }) {
  return (
    <div>
      <p className="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">{label}</p>
      <p className="flex items-center justify-center gap-1 text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
        {value}
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
            fillRule="evenodd"
            clipRule="evenodd"
            d={
              up
                ? 'M7.60141 2.33683C7.73885 2.18084 7.9401 2.08243 8.16435 2.08243C8.16475 2.08243 8.16516 2.08243 8.16556 2.08243C8.35773 2.08219 8.54998 2.15535 8.69664 2.30191L12.6968 6.29924C12.9898 6.59203 12.9899 7.0669 12.6971 7.3599C12.4044 7.6529 11.9295 7.65306 11.6365 7.36027L8.91435 4.64004L8.91435 13.5C8.91435 13.9142 8.57856 14.25 8.16435 14.25C7.75013 14.25 7.41435 13.9142 7.41435 13.5L7.41435 4.64442L4.69679 7.36025C4.4038 7.65305 3.92893 7.6529 3.63613 7.35992C3.34333 7.06693 3.34348 6.59206 3.63646 6.29926L7.60141 2.33683Z'
                : 'M7.26816 13.6632C7.4056 13.8192 7.60686 13.9176 7.8311 13.9176C7.83148 13.9176 7.83187 13.9176 7.83226 13.9176C8.02445 13.9178 8.21671 13.8447 8.36339 13.6981L12.3635 9.70076C12.6565 9.40797 12.6567 8.9331 12.3639 8.6401C12.0711 8.34711 11.5962 8.34694 11.3032 8.63973L8.5811 11.36L8.5811 2.5C8.5811 2.08579 8.24531 1.75 7.8311 1.75C7.41688 1.75 7.0811 2.08579 7.0811 2.5L7.0811 11.3556L4.36354 8.63975C4.07055 8.34695 3.59568 8.3471 3.30288 8.64009C3.01008 8.93307 3.01023 9.40794 3.30321 9.70075L7.26816 13.6632Z'
            }
            fill={up ? '#039855' : '#D92D20'}
          />
        </svg>
      </p>
    </div>
  );
}

export default function Dashboard() {
  const [statsTab, setStatsTab] = useState('overview');

  return (
    <AuthenticatedLayout>
      <Head title="eCommerce Dashboard" />

      <main>
        <div className="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
          <div className="grid grid-cols-12 gap-4 md:gap-6">
            <div className="col-span-12 space-y-6 xl:col-span-7">
              {/* Metric Group One */}
              <MetricGroup />

              {/* Chart One */}
              <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
                <div className="flex items-center justify-between">
                  <h3 className="text-lg font-semibold text-gray-800 dark:text-white/90">Monthly Sales</h3>
                  <CardMenu />
                </div>

                <div className="max-w-full overflow-x-auto custom-scrollbar">
                  <div className="-ml-5 min-w-[650px] pl-2 xl:min-w-full">
                    <ApexChart
                      id="chartOne"
                      options={chartOneOptions}
                      className="-ml-5 h-full min-w-[650px] pl-2 xl:min-w-full"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div className="col-span-12 xl:col-span-5">
              {/* Chart Two */}
              <div className="rounded-2xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="shadow-default rounded-2xl bg-white px-5 pb-11 pt-5 dark:bg-gray-900 sm:px-6 sm:pt-6">
                  <div className="flex justify-between">
                    <div>
                      <h3 className="text-lg font-semibold text-gray-800 dark:text-white/90">Monthly Target</h3>
                      <p className="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                        Target you’ve set for each month
                      </p>
                    </div>
                    <CardMenu />
                  </div>

                  <div className="relative max-h-[195px]">
                    <ApexChart id="chartTwo" options={chartTwoOptions} className="h-full" />
                    <span className="absolute left-1/2 top-[85%] -translate-x-1/2 -translate-y-[85%] rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                      +10%
                    </span>
                  </div>
                  <p className="mx-auto mt-1.5 w-full max-w-[380px] text-center text-sm text-gray-500 sm:text-base">
                    You earn $3287 today, it's higher than last month. Keep up your good work!
                  </p>
                </div>

                <div className="flex items-center justify-center gap-5 px-6 py-3.5 sm:gap-8 sm:py-5">
                  <MonthlyTargetStats label="Target" value="$20K" up={false} />
                  <div className="h-7 w-px bg-gray-200 dark:bg-gray-800" />
                  <MonthlyTargetStats label="Revenue" value="$20K" up />
                  <div className="h-7 w-px bg-gray-200 dark:bg-gray-800" />
                  <MonthlyTargetStats label="Today" value="$20K" up />
                </div>
              </div>
            </div>

            <div className="col-span-12">
              {/* Chart Three */}
              <div className="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
                <div className="mb-6 flex flex-col gap-5 sm:flex-row sm:justify-between">
                  <div className="w-full">
                    <h3 className="text-lg font-semibold text-gray-800 dark:text-white/90">Statistics</h3>
                    <p className="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                      Target you’ve set for each month
                    </p>
                  </div>

                  <div className="flex w-full items-start gap-3 sm:justify-end">
                    <div className="inline-flex w-fit items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                      {statisticsTabs.map((tab) => (
                        <button
                          key={tab}
                          onClick={() => setStatsTab(tab)}
                          className={`text-theme-sm rounded-md px-3 py-2 font-medium capitalize hover:text-gray-900 dark:hover:text-white ${
                            statsTab === tab
                              ? 'shadow-theme-xs bg-white text-gray-900 dark:bg-gray-800 dark:text-white'
                              : 'text-gray-500 dark:text-gray-400'
                          }`}
                        >
                          {tab}
                        </button>
                      ))}
                    </div>

                    <div className="relative max-w-41">
                      <input
                        type="text"
                        readOnly
                        placeholder="Select dates"
                        className="datepicker text-theme-sm shadow-theme-xs h-10 w-full max-w-11 rounded-lg border border-gray-200 bg-white py-2.5 pl-[34px] pr-4 font-medium text-gray-700 focus:outline-hidden focus:ring-0 focus-visible:outline-hidden xl:max-w-fit xl:pl-11 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                      />
                      <div className="pointer-events-none absolute inset-0 left-3 right-auto flex items-center">
                        <svg
                          className="fill-gray-700 dark:fill-gray-400"
                          width="20"
                          height="20"
                          viewBox="0 0 20 20"
                          fill="none"
                          xmlns="http://www.w3.org/2000/svg"
                        >
                          <path
                            fillRule="evenodd"
                            clipRule="evenodd"
                            d="M6.66683 1.54199C7.08104 1.54199 7.41683 1.87778 7.41683 2.29199V3.00033H12.5835V2.29199C12.5835 1.87778 12.9193 1.54199 13.3335 1.54199C13.7477 1.54199 14.0835 1.87778 14.0835 2.29199V3.00033L15.4168 3.00033C16.5214 3.00033 17.4168 3.89576 17.4168 5.00033V7.50033V15.8337C17.4168 16.9382 16.5214 17.8337 15.4168 17.8337H4.5835C3.47893 17.8337 2.5835 16.9382 2.5835 15.8337V7.50033V5.00033C2.5835 3.89576 3.47893 3.00033 4.5835 3.00033L5.91683 3.00033V2.29199C5.91683 1.87778 6.25262 1.54199 6.66683 1.54199ZM6.66683 4.50033H4.5835C4.30735 4.50033 4.0835 4.72418 4.0835 5.00033V6.75033H15.9168V5.00033C15.9168 4.72418 15.693 4.50033 15.4168 4.50033H13.3335H6.66683ZM15.9168 8.25033H4.0835V15.8337C4.0835 16.1098 4.30735 16.3337 4.5835 16.3337H15.4168C15.693 16.3337 15.9168 16.1098 15.9168 15.8337V8.25033Z"
                            fill=""
                          />
                        </svg>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="custom-scrollbar max-w-full overflow-x-auto">
                  <ApexChart id="chartThree" options={chartThreeOptions} className="-ml-4 min-w-[700px] pl-2" />
                </div>
              </div>
            </div>

            <div className="col-span-12 xl:col-span-5">
              {/* Map One */}
              <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <div className="flex justify-between">
                  <div>
                    <h3 className="text-lg font-semibold text-gray-800 dark:text-white/90">Customers Demographic</h3>
                    <p className="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                      Number of customer based on country
                    </p>
                  </div>
                  <CardMenu />
                </div>

                <div className="my-6 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 px-4 py-6 dark:border-gray-800 dark:bg-gray-900 sm:px-6">
                  <WorldMap className="mapOne map-btn -mx-4 -my-6 h-[212px] w-[252px] 2xsm:w-[307px] xsm:w-[358px] sm:-mx-6 md:w-[668px] lg:w-[634px] xl:w-[393px] 2xl:w-[554px]" />
                </div>

                <div className="space-y-5">
                  {[
                    { flag: '/images/country/country-01.svg', alt: 'usa', name: 'USA', customers: '2,379 Customers', percent: 79 },
                    { flag: '/images/country/country-02.svg', alt: 'france', name: 'France', customers: '589 Customers', percent: 23 },
                  ].map((country) => (
                    <div key={country.name} className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className="w-full max-w-8 items-center rounded-full">
                          <img src={country.flag} alt={country.alt} />
                        </div>
                        <div>
                          <p className="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{country.name}</p>
                          <span className="block text-theme-xs text-gray-500 dark:text-gray-400">
                            {country.customers}
                          </span>
                        </div>
                      </div>

                      <div className="flex w-full max-w-[140px] items-center gap-3">
                        <div className="relative block h-2 w-full max-w-[100px] rounded-sm bg-gray-200 dark:bg-gray-800">
                          <div
                            className="absolute left-0 top-0 flex h-full items-center justify-center rounded-sm bg-brand-500 text-xs font-medium text-white"
                            style={{ width: `${country.percent}%` }}
                          />
                        </div>
                        <p className="text-theme-sm font-medium text-gray-800 dark:text-white/90">
                          {country.percent}%
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className="col-span-12 xl:col-span-7">
              {/* Table One */}
              <RecentOrdersTable />
            </div>
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
