# Trivy ignore policy.
# Referenced by the scan-trivy job in .github/workflows/ci.yml via `ignore-policy`.
#
# `linux-libc-dev` ships the Linux kernel headers. A container does not run its
# own kernel — it shares the host's — and these headers are compile-time-only
# files, so kernel CVEs reported against this package are not exploitable inside
# the image. New kernel CVEs land in this package almost weekly, so we filter the
# whole package (rather than individual CVE IDs) to keep CI stable. Real,
# exploitable OS-package CVEs (openssl, glibc, …) still fail the build.
package trivy

default ignore = false

ignore {
	input.PkgName == "linux-libc-dev"
}
