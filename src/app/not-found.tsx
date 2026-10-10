import Link from "next/link";
import { ArrowLeft, Home, Phone } from "lucide-react";

export default function NotFound() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-[#fffdf8] px-4 py-16">
      <div className="text-center max-w-lg mx-auto">
        <span className="text-8xl font-black text-green-700 tracking-tight block mb-2">404</span>
        <h1 className="text-3xl font-bold text-gray-900 mb-3">Page Not Found</h1>
        <p className="text-gray-600 mb-8 leading-relaxed">
          The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
        </p>
        <div className="flex flex-wrap items-center justify-center gap-4">
          <Link
            href="/"
            className="inline-flex items-center gap-2 bg-[#43934a] hover:bg-[#36793c] text-white px-6 py-3 rounded-full font-semibold transition-all shadow-md"
          >
            <Home size={18} />
            <span>Return to Homepage</span>
          </Link>
          <Link
            href="/contact"
            className="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-full font-semibold transition-all shadow-md"
          >
            <Phone size={18} />
            <span>Contact Support</span>
          </Link>
        </div>
      </div>
    </div>
  );
}
