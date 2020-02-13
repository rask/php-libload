# makefile
#
# Responsible for building a test library that PHP can use in PHPUnit tests.

build: testlib1.so.1 testlib1.so.2 testlib2.so.1 testlib2.so.2 testlib1.so.3

testlib1.so.1: testlib1.c
	gcc -shared -o dlpath/testlib1.so -fPIC testlib1.c

testlib1.so.2: testlib1.c
	gcc -shared -o dylib/testlib1.so -fPIC testlib1.c

testlib2.so.1: testlib2.c
	gcc -shared -o dlpath/testlib2.so -fPIC testlib2.c

testlib2.so.2: testlib2.c
	gcc -shared -o dylib/testlib2.so -fPIC testlib2.c

testlib1.so.3: testlib1.c
	gcc -shared -o testlib1.so -fPIC testlib2.c

clean:
	-rm dylib/testlib1.so dlpath/testlib1.so dylib/testlib2.so dlpath/testlib2.so testlib1.so
