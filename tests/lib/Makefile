# makefile
#
# Responsible for building a test library that PHP can use in PHPUnit tests.

build: testlib.so

testlib.so: testlib.c
	gcc -shared -o testlib.so -fPIC testlib.c

clean:
	rm testlib.so
