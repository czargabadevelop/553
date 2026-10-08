export default function FlashMessage({ message }) {
  if (!message) {
    return null;
  }

  return (
    <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">
      {message}
    </div>
  );
}
